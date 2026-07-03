<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Notification;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Restock;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ==============================
        // 1. USERS
        // ==============================
        $owner = User::create([
            'name' => 'Ci Ali',
            'username' => 'ciali',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $karyawan1 = User::create([
            'name' => 'Budi Santoso',
            'username' => 'budi',
            'password' => Hash::make('password123'),
            'role' => 'karyawan',
            'is_active' => true,
        ]);

        $karyawan2 = User::create([
            'name' => 'Siti Rahma',
            'username' => 'siti',
            'password' => Hash::make('password123'),
            'role' => 'karyawan',
            'is_active' => true,
        ]);

        $users = [$owner, $karyawan1, $karyawan2];

        // ==============================
        // 1.5. MASTER UNITS
        // ==============================
        $masterUnitsList = ['buah', 'pcs', 'lusin', 'box', 'batang', 'sak', 'kg', 'ton', 'lonjor', 'ikat'];
        foreach ($masterUnitsList as $unitName) {
            \App\Models\Unit::create(['name' => $unitName, 'symbol' => $unitName]);
        }

        // ==============================
        // 2. CATEGORIES
        // ==============================
        $categories = [
            'Aksesoris Rumah Tangga' => Category::create(['name' => 'Aksesoris Rumah Tangga']),
            'Bahan Bangunan' => Category::create(['name' => 'Bahan Bangunan']),
            'Sparepart Laptop' => Category::create(['name' => 'Sparepart Laptop']),
        ];

        // ==============================
        // 3. PRODUCTS + PRODUCT UNITS + INITIAL RESTOCKS
        // ==============================
        $productData = [
            // --- Bahan Bangunan (3) ---
            [
                'name' => 'Semen Tiga Roda',
                'category' => 'Bahan Bangunan',
                'base_unit' => 'kg',
                'cost_price' => 2500,
                'selling_price' => 3200,
                'location' => 'Gudang Muntilan',
                'min_stock' => 500,
                'initial_stock' => 2500,
                'units' => [
                    ['name' => 'sak', 'factor' => 50],
                    ['name' => 'ton', 'factor' => 1000],
                ],
            ],
            [
                'name' => 'Besi Beton 8mm',
                'category' => 'Bahan Bangunan',
                'base_unit' => 'batang',
                'cost_price' => 45000,
                'selling_price' => 55000,
                'location' => 'Gudang Muntilan',
                'min_stock' => 20,
                'initial_stock' => 100,
                'units' => [
                    ['name' => 'lonjor', 'factor' => 1],
                    ['name' => 'ikat', 'factor' => 12],
                ],
            ],
            [
                'name' => 'Pipa PVC AW 1/2"',
                'category' => 'Bahan Bangunan',
                'base_unit' => 'batang',
                'cost_price' => 25000,
                'selling_price' => 32000,
                'location' => 'Gudang Muntilan',
                'min_stock' => 20,
                'initial_stock' => 80,
                'units' => [
                    ['name' => 'batang', 'factor' => 1],
                    ['name' => 'lusin', 'factor' => 12],
                ],
            ],

            // --- Sparepart Laptop (3) ---
            [
                'name' => 'SSD V-Gen SATA 256GB',
                'category' => 'Sparepart Laptop',
                'base_unit' => 'pcs',
                'cost_price' => 250000,
                'selling_price' => 320000,
                'location' => 'Etalase Depan',
                'min_stock' => 5,
                'initial_stock' => 15,
                'units' => [
                    ['name' => 'pcs', 'factor' => 1],
                    ['name' => 'box', 'factor' => 10],
                ],
            ],
            [
                'name' => 'RAM DDR4 SODIMM 8GB',
                'category' => 'Sparepart Laptop',
                'base_unit' => 'pcs',
                'cost_price' => 180000,
                'selling_price' => 240000,
                'location' => 'Etalase Depan',
                'min_stock' => 5,
                'initial_stock' => 20,
                'units' => [
                    ['name' => 'pcs', 'factor' => 1],
                    ['name' => 'box', 'factor' => 10],
                ],
            ],
            [
                'name' => 'Keyboard Laptop Universal',
                'category' => 'Sparepart Laptop',
                'base_unit' => 'pcs',
                'cost_price' => 90000,
                'selling_price' => 125000,
                'location' => 'Etalase Depan',
                'min_stock' => 3,
                'initial_stock' => 12,
                'units' => [
                    ['name' => 'pcs', 'factor' => 1],
                ],
            ],

            // --- Aksesoris Rumah Tangga (3) ---
            [
                'name' => 'Kran Air Stainless 1/2"',
                'category' => 'Aksesoris Rumah Tangga',
                'base_unit' => 'buah',
                'cost_price' => 25000,
                'selling_price' => 35000,
                'location' => 'Etalase Depan',
                'min_stock' => 5,
                'initial_stock' => 24,
                'units' => [
                    ['name' => 'buah', 'factor' => 1],
                    ['name' => 'lusin', 'factor' => 12],
                ],
            ],
            [
                'name' => 'Kuas Cat 3 Inch',
                'category' => 'Aksesoris Rumah Tangga',
                'base_unit' => 'buah',
                'cost_price' => 8000,
                'selling_price' => 12000,
                'location' => 'Etalase Depan',
                'min_stock' => 10,
                'initial_stock' => 20,
                'units' => [
                    ['name' => 'buah', 'factor' => 1],
                    ['name' => 'lusin', 'factor' => 12],
                ],
            ],
            [
                'name' => 'Gembok Kuningan 40mm',
                'category' => 'Aksesoris Rumah Tangga',
                'base_unit' => 'buah',
                'cost_price' => 15000,
                'selling_price' => 22000,
                'location' => 'Etalase Depan',
                'min_stock' => 5,
                'initial_stock' => 15,
                'units' => [
                    ['name' => 'buah', 'factor' => 1],
                    ['name' => 'lusin', 'factor' => 12],
                ],
            ],
        ];

        $products = [];

        foreach ($productData as $pd) {
            $baseUnitModel = \App\Models\Unit::firstOrCreate(
                ['name' => trim(strtolower($pd['base_unit']))],
                ['symbol' => trim(strtolower($pd['base_unit']))]
            );

            $product = Product::create([
                'name' => $pd['name'],
                'category_id' => $categories[$pd['category']]->id,
                'base_unit_id' => $baseUnitModel->id,
                'cost_price_per_base_unit' => $pd['cost_price'],
                'selling_price_per_base_unit' => $pd['selling_price'],
                'stock_qty_base_unit' => $pd['initial_stock'],
                'location' => $pd['location'],
                'min_stock_threshold' => $pd['min_stock'],
                'is_active' => true,
            ]);

            // Create unit jual
            foreach ($pd['units'] as $unit) {
                $altUnitModel = \App\Models\Unit::firstOrCreate(
                    ['name' => trim(strtolower($unit['name']))],
                    ['symbol' => trim(strtolower($unit['name']))]
                );

                ProductUnit::create([
                    'product_id' => $product->id,
                    'unit_id' => $altUnitModel->id,
                    'conversion_factor' => $unit['factor'],
                ]);
            }

            // Initial restock record
            $restockUnit = $pd['units'][0];
            Restock::create([
                'product_id' => $product->id,
                'qty_base_unit' => $pd['initial_stock'],
                'unit_name_at_restock' => $restockUnit['name'],
                'cost_price_per_base_unit_at_restock' => $pd['cost_price'],
                'location' => $pd['location'],
                'restocked_by_user_id' => $owner->id,
                'restocked_at' => now()->subDays(14),
            ]);

            $products[] = $product;
        }

        // ==============================
        // 4. ADDITIONAL RESTOCKS
        // ==============================
        $semenTR = $products[0];
        Restock::create([
            'product_id' => $semenTR->id,
            'qty_base_unit' => 1500, // 30 sak
            'unit_name_at_restock' => 'sak',
            'cost_price_per_base_unit_at_restock' => 2700, // naik dari 2500
            'location' => 'Gudang Muntilan',
            'restocked_by_user_id' => $karyawan1->id,
            'restocked_at' => now()->subDays(7),
        ]);
        // Update weighted average
        $newCost = ($semenTR->stock_qty_base_unit * $semenTR->cost_price_per_base_unit + 1500 * 2700) / ($semenTR->stock_qty_base_unit + 1500);
        $semenTR->update([
            'stock_qty_base_unit' => $semenTR->stock_qty_base_unit + 1500,
            'cost_price_per_base_unit' => round($newCost, 2),
        ]);

        // Notifikasi restock
        Notification::create([
            'recipient_user_id' => $owner->id,
            'type' => 'restock',
            'related_restock_id' => 2,
            'is_anomaly' => false,
            'message' => 'Restock: Semen Tiga Roda — 30 sak (1.500 kg) oleh Budi Santoso. HPP: Rp 2.700/kg.',
            'is_read' => true,
            'created_at' => now()->subDays(7),
        ]);

        // ==============================
        // 5. SAMPLE TRANSACTIONS
        // ==============================
        $txnData = [
            // Transaksi 1: Budi jual semen 3 sak + besi beton 2 batang (6 hari lalu)
            [
                'cashier' => $karyawan1, 'method' => 'tunai', 'discount' => 0, 'days_ago' => 6,
                'items' => [
                    ['product_idx' => 0, 'unit' => 'sak', 'qty' => 3],
                    ['product_idx' => 1, 'unit' => 'lonjor', 'qty' => 2],
                ],
            ],
            // Transaksi 2: Siti jual besi beton 8mm 1 ikat (5 hari lalu)
            [
                'cashier' => $karyawan2, 'method' => 'qris', 'discount' => 0, 'days_ago' => 5,
                'items' => [
                    ['product_idx' => 1, 'unit' => 'ikat', 'qty' => 1],
                ],
            ],
            // Transaksi 3: Owner jual SSD 2 pcs + kuas 3 buah, diskon 10.000 (4 hari lalu)
            [
                'cashier' => $owner, 'method' => 'tunai', 'discount' => 10000, 'days_ago' => 4,
                'items' => [
                    ['product_idx' => 3, 'unit' => 'pcs', 'qty' => 2],
                    ['product_idx' => 7, 'unit' => 'buah', 'qty' => 3],
                ],
            ],
            // Transaksi 4: Budi jual pipa 1/2" 5 batang (3 hari lalu)
            [
                'cashier' => $karyawan1, 'method' => 'tunai', 'discount' => 0, 'days_ago' => 3,
                'items' => [
                    ['product_idx' => 2, 'unit' => 'batang', 'qty' => 5],
                ],
            ],
            // Transaksi 5: Siti jual RAM 1 pcs (1 hari lalu)
            [
                'cashier' => $karyawan2, 'method' => 'qris', 'discount' => 0, 'days_ago' => 1,
                'items' => [
                    ['product_idx' => 4, 'unit' => 'pcs', 'qty' => 1],
                ],
            ],
            // Transaksi 6: Owner jual semen tiga roda 2 sak + kuas 5 buah (hari ini)
            [
                'cashier' => $owner, 'method' => 'tunai', 'discount' => 0, 'days_ago' => 0,
                'items' => [
                    ['product_idx' => 0, 'unit' => 'sak', 'qty' => 2],
                    ['product_idx' => 7, 'unit' => 'buah', 'qty' => 5],
                ],
            ],
        ];

        foreach ($txnData as $tx) {
            $subtotal = 0;
            $itemsForTxn = [];

            foreach ($tx['items'] as $item) {
                $product = $products[$item['product_idx']];
                $targetUnit = \App\Models\Unit::where('name', $item['unit'])->first();
                $unit = $targetUnit ? ProductUnit::where('product_id', $product->id)
                    ->where('unit_id', $targetUnit->id)
                    ->first() : null;

                // If unit is the base unit itself
                $conversionFactor = $unit ? $unit->conversion_factor : 1;
                $unitName = $targetUnit ? $targetUnit->name : ($product->baseUnit->name ?? 'unit');

                $pricePerUnit = $product->selling_price_per_base_unit * $conversionFactor;
                $lineTotal = $pricePerUnit * $item['qty'];
                $subtotal += $lineTotal;

                $itemsForTxn[] = [
                    'product' => $product,
                    'unit_name' => $unitName,
                    'qty' => $item['qty'],
                    'conversion_factor' => $conversionFactor,
                    'price_per_unit' => $pricePerUnit,
                    'cost_price_base' => $product->cost_price_per_base_unit,
                    'qty_base' => $item['qty'] * $conversionFactor,
                ];
            }

            $txnDatetime = now()->subDays($tx['days_ago'])->subHours(rand(1, 10));

            $totalAmount = $subtotal - $tx['discount'];
            $cashReceived = $tx['method'] === 'tunai' ? (float) (ceil($totalAmount / 50000) * 50000) : $totalAmount;

            $transaction = Transaction::create([
                'cashier_user_id' => $tx['cashier']->id,
                'transaction_datetime' => $txnDatetime,
                'payment_method' => $tx['method'],
                'subtotal_before_discount' => $subtotal,
                'discount_amount' => $tx['discount'],
                'total_amount' => $totalAmount,
                'cash_received' => $cashReceived,
                'created_at' => $txnDatetime,
            ]);

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

                // Reduce stock
                $item['product']->decrement('stock_qty_base_unit', $item['qty_base']);
            }
        }

        // ==============================
        // 6. ADDITIONAL NOTIFICATIONS
        // ==============================
        Notification::create([
            'recipient_user_id' => $owner->id,
            'type' => 'restock',
            'related_restock_id' => 1,
            'is_anomaly' => false,
            'message' => 'Restock awal: Semen Tiga Roda — 50 sak (2.500 kg) oleh Ci Ali. HPP: Rp 2.500/kg.',
            'is_read' => true,
            'created_at' => now()->subDays(14),
        ]);

        Notification::create([
            'recipient_user_id' => $owner->id,
            'type' => 'restock',
            'is_anomaly' => true,
            'message' => '⚠️ Restock perlu dicek: Kuas Cat 3 Inch — 50 buah oleh Budi Santoso. HPP: Rp 12.000/kg (berbeda >20% dari HPP saat ini Rp 8.000/kg).',
            'is_read' => false,
            'created_at' => now()->subHours(3),
        ]);

        Notification::create([
            'recipient_user_id' => $owner->id,
            'type' => 'backup',
            'is_anomaly' => false,
            'message' => 'Backup harian berhasil dibuat. Klik untuk download.',
            'is_read' => false,
            'created_at' => now()->subHours(1),
        ]);

        $this->call(SalesHistorySeeder::class);
    }
}

