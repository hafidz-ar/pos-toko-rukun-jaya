<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TransactionController extends Controller
{
    /**
     * Show the kasir page with products.
     */
    public function create(Request $request)
    {
        $products = Product::with(['category', 'baseUnit', 'units.unit'])
            ->active()
            ->where('stock_qty_base_unit', '>', 0)
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category->name,
                'base_unit' => $p->baseUnit->name,
                'selling_price_per_base_unit' => (float) $p->selling_price_per_base_unit,
                'cost_price_per_base_unit' => (float) $p->cost_price_per_base_unit,
                'stock_qty_base_unit' => (float) $p->stock_qty_base_unit,
                'location' => $p->location,
                'photo_url' => $p->photo_url,
                'units' => $p->units->map(fn ($u) => [
                    'id' => $u->id,
                    'unit_name' => $u->unit->name,
                    'conversion_factor' => (float) $u->conversion_factor,
                    'selling_price' => $u->selling_price !== null ? (float) $u->selling_price : (float) round($p->selling_price_per_base_unit * $u->conversion_factor),
                ]),
            ]);

        return Inertia::render('Kasir', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    /**
     * Search products (API endpoint for kasir real-time search).
     */
    public function searchProducts(Request $request)
    {
        $search = $request->get('q', '');

        $products = Product::with(['category', 'baseUnit', 'units.unit'])
            ->active()
            ->where('stock_qty_base_unit', '>', 0)
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('category', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
                
                if (preg_match('/^(sku-)?(\d+)$/i', $search, $matches)) {
                    $id = (int)$matches[2];
                    $q->orWhere('id', $id);
                }
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category->name,
                'base_unit' => $p->baseUnit->name,
                'selling_price_per_base_unit' => (float) $p->selling_price_per_base_unit,
                'cost_price_per_base_unit' => (float) $p->cost_price_per_base_unit,
                'stock_qty_base_unit' => (float) $p->stock_qty_base_unit,
                'location' => $p->location,
                'photo_url' => $p->photo_url,
                'units' => $p->units->map(fn ($u) => [
                    'id' => $u->id,
                    'unit_name' => $u->unit->name,
                    'conversion_factor' => (float) $u->conversion_factor,
                    'selling_price' => $u->selling_price !== null ? (float) $u->selling_price : (float) round($p->selling_price_per_base_unit * $u->conversion_factor),
                ]),
            ]);

        return response()->json($products);
    }

    /**
     * Process a new transaction.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:tunai,qris',
            'discount_amount' => 'nullable|numeric|min:0',
            'cash_received' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.unit_name' => 'required|string',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.conversion_factor' => 'required|numeric|min:0.0001',
            'items.*.price_per_unit' => 'required|numeric|min:0',
        ]);

        $discountAmount = $validated['discount_amount'] ?? 0;

        try {
            return DB::transaction(function () use ($validated, $discountAmount) {
                $subtotal = 0;
                $totalHPP = 0;
                $itemsToCreate = [];

                foreach ($validated['items'] as $item) {
                    // Lock product row to prevent race condition
                    $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                    $conversionFactor = $item['conversion_factor'];
                    $qtyInBaseUnit = $item['qty'] * $conversionFactor;

                    // Validate stock sufficiency
                    if ($product->stock_qty_base_unit < $qtyInBaseUnit) {
                        throw new \Exception(
                            "Stok {$product->name} tidak cukup. " .
                            "Tersedia: " . number_format($product->stock_qty_base_unit, 0) . " {$product->baseUnit->name}, " .
                            "Dibutuhkan: " . number_format($qtyInBaseUnit, 0) . " {$product->baseUnit->name}."
                        );
                    }

                    $lineTotal = $item['price_per_unit'] * $item['qty'];
                    $lineHPP = $product->cost_price_per_base_unit * $qtyInBaseUnit;

                    $subtotal += $lineTotal;
                    $totalHPP += $lineHPP;

                    $itemsToCreate[] = [
                        'product' => $product,
                        'unit_name_at_transaction' => $item['unit_name'],
                        'qty_in_selected_unit' => $item['qty'],
                        'conversion_factor_at_transaction' => $conversionFactor,
                        'price_per_unit_at_transaction' => $item['price_per_unit'],
                        'cost_price_per_base_unit_at_transaction' => $product->cost_price_per_base_unit,
                        'qty_base_unit' => $qtyInBaseUnit,
                    ];
                }

                // Floor price validation: total after discount must not be below total HPP
                $totalAmount = $subtotal - $discountAmount;
                if ($totalAmount < $totalHPP) {
                    throw new \Exception(
                        "Diskon melebihi batas! Total setelah diskon (Rp " . number_format($totalAmount, 0, ',', '.') . ") " .
                        "di bawah total harga modal (Rp " . number_format($totalHPP, 0, ',', '.') . "). " .
                        "Kurangi diskon sebesar minimal Rp " . number_format($totalHPP - $totalAmount, 0, ',', '.') . "."
                    );
                }

                $cashReceived = $validated['payment_method'] === 'tunai' 
                    ? (float) ($validated['cash_received'] ?? $totalAmount) 
                    : $totalAmount;

                // Create transaction
                $transaction = Transaction::create([
                    'cashier_user_id' => auth()->id(),
                    'transaction_datetime' => now(),
                    'payment_method' => $validated['payment_method'],
                    'subtotal_before_discount' => $subtotal,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                    'cash_received' => $cashReceived,
                    'created_at' => now(),
                ]);

                // Create items and reduce stock
                foreach ($itemsToCreate as $itemData) {
                    TransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $itemData['product']->id,
                        'unit_name_at_transaction' => $itemData['unit_name_at_transaction'],
                        'qty_in_selected_unit' => $itemData['qty_in_selected_unit'],
                        'conversion_factor_at_transaction' => $itemData['conversion_factor_at_transaction'],
                        'price_per_unit_at_transaction' => $itemData['price_per_unit_at_transaction'],
                        'cost_price_per_base_unit_at_transaction' => $itemData['cost_price_per_base_unit_at_transaction'],
                    ]);

                    // Reduce stock
                    $itemData['product']->decrement('stock_qty_base_unit', $itemData['qty_base_unit']);
                }

                // Return receipt data
                $transaction->load(['cashier', 'items.product']);

                return response()->json([
                    'success' => true,
                    'receipt' => [
                        'id' => $transaction->id,
                        'datetime' => $transaction->transaction_datetime->format('d/m/Y H:i'),
                        'cashier' => $transaction->cashier->name,
                        'payment_method' => $transaction->payment_method,
                        'items' => $transaction->items->map(fn ($i) => [
                            'product_name' => $i->product->name,
                            'unit' => $i->unit_name_at_transaction,
                            'qty' => (float) $i->qty_in_selected_unit,
                            'price' => (float) $i->price_per_unit_at_transaction,
                            'subtotal' => (float) $i->subtotal,
                        ]),
                        'subtotal' => (float) $transaction->subtotal_before_discount,
                        'discount' => (float) $transaction->discount_amount,
                        'total' => (float) $transaction->total_amount,
                        'cash_received' => (float) $transaction->cash_received,
                        'change' => $transaction->payment_method === 'tunai' 
                            ? (float) max(0, $transaction->cash_received - $transaction->total_amount) 
                            : 0.0,
                    ],
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
