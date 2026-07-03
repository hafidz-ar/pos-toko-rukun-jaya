<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Restock;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SalesHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get all users to act as cashiers
        $users = User::all();
        if ($users->isEmpty()) {
            return;
        }

        $owner = $users->where('role', 'owner')->first() ?? $users->first();

        // 2. Get all active products with their units and base unit
        $products = Product::with(['baseUnit', 'units.unit'])->active()->get();
        if ($products->isEmpty()) {
            return;
        }

        // 3. Generate 100 random transaction datetimes from 3 months ago until now, sorted chronologically
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subMonths(3);
        $totalTransactions = 100;
        
        $datetimes = [];
        for ($i = 0; $i < $totalTransactions; $i++) {
            $randomTimestamp = rand($startDate->timestamp, $endDate->timestamp);
            $datetimes[] = Carbon::createFromTimestamp($randomTimestamp);
        }
        
        // Sort datetimes chronologically
        usort($datetimes, function ($a, $b) {
            return $a->timestamp <=> $b->timestamp;
        });

        foreach ($datetimes as $txnDatetime) {
            // Select random cashier
            $cashier = $users->random();

            // Select payment method (60% tunai, 40% qris)
            $paymentMethod = rand(1, 100) <= 60 ? 'tunai' : 'qris';

            // Random number of items: 1 to 4
            $numItems = rand(1, 4);
            
            // Pick random products for this transaction (without duplicates)
            $txnProducts = $products->random(min($numItems, $products->count()));

            $subtotal = 0;
            $totalCostPrice = 0;
            $itemsForTxn = [];

            foreach ($txnProducts as $product) {
                // Determine unit: 70% base unit, 30% alternate unit (if exists)
                $alternateUnits = $product->units;
                $useBaseUnit = $alternateUnits->isEmpty() || (rand(1, 100) <= 70);

                if ($useBaseUnit) {
                    $unitName = $product->baseUnit->name;
                    $conversionFactor = 1.0;
                    $pricePerUnit = (float) $product->selling_price_per_base_unit;
                } else {
                    $prodUnit = $alternateUnits->random();
                    $unitName = $prodUnit->unit->name;
                    $conversionFactor = (float) $prodUnit->conversion_factor;
                    $pricePerUnit = $prodUnit->selling_price !== null 
                        ? (float) $prodUnit->selling_price 
                        : (float) round($product->selling_price_per_base_unit * $conversionFactor);
                }

                // Random quantity in selected unit:
                if ($unitName === 'kg') {
                    $qty = rand(5, 50); // e.g. 5 to 50 kg
                } else {
                    $qty = rand(1, 5);
                }

                $qtyBaseUnit = $qty * $conversionFactor;

                // Check and handle stock: if product stock is below minimum threshold or less than qtyBaseUnit,
                // we trigger a Restock first to simulate store restocking operations.
                if ($product->stock_qty_base_unit < $qtyBaseUnit || $product->stock_qty_base_unit <= $product->min_stock_threshold) {
                    // Decide a restock quantity: 2 to 5 times the min threshold or a standard large quantity
                    $restockQty = max(50.0, (float) ($product->min_stock_threshold * rand(3, 8)));
                    
                    // Semen and other bulk goods get larger restocks
                    if ($product->baseUnit->name === 'kg') {
                        $restockQty = 1000.0; // 1 ton
                    }

                    // Create the Restock record
                    Restock::create([
                        'product_id' => $product->id,
                        'qty_base_unit' => $restockQty,
                        'unit_name_at_restock' => $product->baseUnit->name,
                        'cost_price_per_base_unit_at_restock' => $product->cost_price_per_base_unit,
                        'location' => $product->location ?? 'Gudang Muntilan',
                        'restocked_by_user_id' => $owner->id,
                        'restocked_at' => (clone $txnDatetime)->subMinutes(rand(10, 180)),
                    ]);

                    // Increment product stock in DB and in memory
                    $product->increment('stock_qty_base_unit', $restockQty);
                    $product->stock_qty_base_unit += $restockQty;
                }

                $lineTotal = $pricePerUnit * $qty;
                $lineCost = $product->cost_price_per_base_unit * $qtyBaseUnit;

                $subtotal += $lineTotal;
                $totalCostPrice += $lineCost;

                $itemsForTxn[] = [
                    'product' => $product,
                    'unit_name' => $unitName,
                    'qty' => $qty,
                    'conversion_factor' => $conversionFactor,
                    'price_per_unit' => $pricePerUnit,
                    'cost_price_base' => $product->cost_price_per_base_unit,
                    'qty_base' => $qtyBaseUnit,
                ];
            }

            // Calculate discount:
            // 15% chance of applying a discount, but ensure total amount >= total HPP
            $discount = 0.0;
            if (rand(1, 100) <= 15 && $subtotal > $totalCostPrice) {
                $maxDiscount = $subtotal - $totalCostPrice;
                if ($maxDiscount > 5000) {
                    $allowedMax = min($maxDiscount * 0.5, 50000);
                    if ($allowedMax >= 5000) {
                        $discountSteps = range(5000, $allowedMax, 5000);
                        if (!empty($discountSteps)) {
                            $discount = (float) $discountSteps[array_rand($discountSteps)];
                        }
                    }
                }
            }

            $totalAmount = $subtotal - $discount;

            // Calculate cash received
            if ($paymentMethod === 'tunai') {
                $possibleAmounts = [
                    ceil($totalAmount / 5000) * 5000,
                    ceil($totalAmount / 10000) * 10000,
                    ceil($totalAmount / 20000) * 20000,
                    ceil($totalAmount / 50000) * 50000,
                    ceil($totalAmount / 100000) * 100000,
                ];
                $validAmounts = array_filter($possibleAmounts, fn($amt) => $amt >= $totalAmount);
                $cashReceived = !empty($validAmounts) ? (float) min($validAmounts) : $totalAmount;
            } else {
                $cashReceived = $totalAmount;
            }

            // Create Transaction
            $transaction = Transaction::create([
                'cashier_user_id' => $cashier->id,
                'transaction_datetime' => $txnDatetime,
                'payment_method' => $paymentMethod,
                'subtotal_before_discount' => $subtotal,
                'discount_amount' => $discount,
                'total_amount' => $totalAmount,
                'cash_received' => $cashReceived,
                'created_at' => $txnDatetime,
            ]);

            // Create Transaction Items and decrement stock
            foreach ($itemsForTxn as $item) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item['product']->id,
                    'unit_name_at_transaction' => $item['unit_name'],
                    'qty_in_selected_unit' => $item['qty'],
                    'conversion_factor_at_transaction' => $item['conversion_factor'],
                    'price_per_unit_at_transaction' => $item['price_per_unit'],
                    'cost_price_per_base_unit_at_transaction' => $item['cost_price_base'],
                ]);

                // Decrement product stock in DB and in memory
                $item['product']->decrement('stock_qty_base_unit', $item['qty_base']);
                $item['product']->stock_qty_base_unit -= $item['qty_base'];
            }
        }
    }
}
