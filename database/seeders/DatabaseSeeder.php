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
        // 2. CATEGORIES
        // ==============================
        $categories = [
            'Semen' => Category::create(['name' => 'Semen']),
            'Besi' => Category::create(['name' => 'Besi']),
            'Pipa' => Category::create(['name' => 'Pipa']),
            'Cat' => Category::create(['name' => 'Cat']),
            'Kayu' => Category::create(['name' => 'Kayu']),
            'Perkakas' => Category::create(['name' => 'Perkakas']),
            'Atap' => Category::create(['name' => 'Atap']),
            'Aksesoris' => Category::create(['name' => 'Aksesoris']),
        ];

        // ==============================
        // 3. PRODUCTS + PRODUCT UNITS + INITIAL RESTOCKS
        // ==============================
        $productData = [
            [
                'name' => 'Semen Tiga Roda',
                'category' => 'Semen',
                'base_unit' => 'kg',
                'cost_price' => 2500,   // per kg
                'selling_price' => 3200, // per kg
                'location' => 'Gudang Belakang',
                'min_stock' => 500,
                'initial_stock' => 2500, // 50 sak
                'units' => [
                    ['name' => 'sak', 'factor' => 50],    // 1 sak = 50 kg
                    ['name' => 'ton', 'factor' => 1000],   // 1 ton = 1000 kg
                ],
            ],
            [
                'name' => 'Semen Holcim',
                'category' => 'Semen',
                'base_unit' => 'kg',
                'cost_price' => 2300,
                'selling_price' => 3000,
                'location' => 'Gudang Belakang',
                'min_stock' => 500,
                'initial_stock' => 2000, // 40 sak
                'units' => [
                    ['name' => 'sak', 'factor' => 50],
                    ['name' => 'ton', 'factor' => 1000],
                ],
            ],
            [
                'name' => 'Besi Beton 8mm',
                'category' => 'Besi',
                'base_unit' => 'batang',
                'cost_price' => 45000,
                'selling_price' => 55000,
                'location' => 'Gudang Belakang',
                'min_stock' => 20,
                'initial_stock' => 100,
                'units' => [
                    ['name' => 'lonjor', 'factor' => 1],  // 1 lonjor = 1 batang
                    ['name' => 'ikat', 'factor' => 12],    // 1 ikat = 12 batang
                ],
            ],
            [
                'name' => 'Besi Beton 10mm',
                'category' => 'Besi',
                'base_unit' => 'batang',
                'cost_price' => 65000,
                'selling_price' => 78000,
                'location' => 'Gudang Belakang',
                'min_stock' => 15,
                'initial_stock' => 60,
                'units' => [
                    ['name' => 'lonjor', 'factor' => 1],
                    ['name' => 'ikat', 'factor' => 12],
                ],
            ],
            [
                'name' => 'Pipa PVC AW 1/2"',
                'category' => 'Pipa',
                'base_unit' => 'batang',
                'cost_price' => 25000,
                'selling_price' => 32000,
                'location' => 'Rak A1',
                'min_stock' => 20,
                'initial_stock' => 80,
                'units' => [
                    ['name' => 'batang', 'factor' => 1],
                    ['name' => 'lusin', 'factor' => 12],
                ],
            ],
            [
                'name' => 'Pipa PVC AW 3/4"',
                'category' => 'Pipa',
                'base_unit' => 'batang',
                'cost_price' => 35000,
                'selling_price' => 45000,
                'location' => 'Rak A1',
                'min_stock' => 15,
                'initial_stock' => 50,
                'units' => [
                    ['name' => 'batang', 'factor' => 1],
                    ['name' => 'lusin', 'factor' => 12],
                ],
            ],
            [
                'name' => 'Cat Dulux Catylac Putih',
                'category' => 'Cat',
                'base_unit' => 'kg',
                'cost_price' => 15000,
                'selling_price' => 20000,
                'location' => 'Rak B1',
                'min_stock' => 25,
                'initial_stock' => 125, // 5 pail
                'units' => [
                    ['name' => 'pail', 'factor' => 25],   // 1 pail = 25 kg
                    ['name' => 'galon', 'factor' => 5],    // 1 galon = 5 kg
                ],
            ],
            [
                'name' => 'Cat Jotun Jotashield',
                'category' => 'Cat',
                'base_unit' => 'kg',
                'cost_price' => 40000,
                'selling_price' => 52000,
                'location' => 'Rak B1',
                'min_stock' => 10,
                'initial_stock' => 50, // 2 pail
                'units' => [
                    ['name' => 'pail', 'factor' => 25],
                    ['name' => 'galon', 'factor' => 5],
                ],
            ],
            [
                'name' => 'Paku Kayu 3 Inch',
                'category' => 'Perkakas',
                'base_unit' => 'kg',
                'cost_price' => 18000,
                'selling_price' => 25000,
                'location' => 'Rak A2',
                'min_stock' => 5,
                'initial_stock' => 30,
                'units' => [
                    ['name' => 'dus', 'factor' => 10],   // 1 dus = 10 kg
                ],
            ],
            [
                'name' => 'Seng Gelombang 180cm',
                'category' => 'Atap',
                'base_unit' => 'lembar',
                'cost_price' => 45000,
                'selling_price' => 58000,
                'location' => 'Gudang Belakang',
                'min_stock' => 10,
                'initial_stock' => 40,
                'units' => [
                    ['name' => 'lembar', 'factor' => 1],
                    ['name' => 'kodi', 'factor' => 20],   // 1 kodi = 20 lembar
                ],
            ],
            [
                'name' => 'Triplek 12mm',
                'category' => 'Kayu',
                'base_unit' => 'lembar',
                'cost_price' => 120000,
                'selling_price' => 150000,
                'location' => 'Gudang Belakang',
                'min_stock' => 5,
                'initial_stock' => 20,
                'units' => [
                    ['name' => 'lembar', 'factor' => 1],
                ],
            ],
            [
                'name' => 'Kran Air Stainless 1/2"',
                'category' => 'Aksesoris',
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
                'name' => 'Kawat Bendrat',
                'category' => 'Besi',
                'base_unit' => 'kg',
                'cost_price' => 12000,
                'selling_price' => 16000,
                'location' => 'Rak A2',
                'min_stock' => 10,
                'initial_stock' => 50,
                'units' => [
                    ['name' => 'roll', 'factor' => 25],   // 1 roll = 25 kg
                ],
            ],
            [
                'name' => 'Kuas Cat 3 Inch',
                'category' => 'Perkakas',
                'base_unit' => 'buah',
                'cost_price' => 8000,
                'selling_price' => 12000,
                'location' => 'Etalase Depan',
                'min_stock' => 10,
                'initial_stock' => 3, // stok menipis — for testing
                'units' => [
                    ['name' => 'buah', 'factor' => 1],
                    ['name' => 'lusin', 'factor' => 12],
                ],
            ],
            [
                'name' => 'Bata Ringan Hebel',
                'category' => 'Semen',
                'base_unit' => 'buah',
                'cost_price' => 8500,
                'selling_price' => 11000,
                'location' => 'Gudang Belakang',
                'min_stock' => 100,
                'initial_stock' => 50, // stok menipis — for testing
                'units' => [
                    ['name' => 'buah', 'factor' => 1],
                    ['name' => 'kubik', 'factor' => 133],  // ≈133 buah per m³
                ],
            ],
        ];

        $products = [];

        foreach ($productData as $pd) {
            $product = Product::create([
                'name' => $pd['name'],
                'category_id' => $categories[$pd['category']]->id,
                'base_unit' => $pd['base_unit'],
                'cost_price_per_base_unit' => $pd['cost_price'],
                'selling_price_per_base_unit' => $pd['selling_price'],
                'stock_qty_base_unit' => $pd['initial_stock'],
                'location' => $pd['location'],
                'min_stock_threshold' => $pd['min_stock'],
                'is_active' => true,
            ]);

            // Create unit jual
            foreach ($pd['units'] as $unit) {
                ProductUnit::create([
                    'product_id' => $product->id,
                    'unit_name' => $unit['name'],
                    'conversion_factor' => $unit['factor'],
                ]);
            }

            // Initial restock record
            $restockUnit = $pd['units'][0];
            $qtyInUnit = $pd['initial_stock'] / $restockUnit['factor'];
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
        // 4. ADDITIONAL RESTOCKS (untuk riwayat + weighted avg testing)
        // ==============================
        // Semen Tiga Roda — restock kedua dengan HPP sedikit naik
        $semenTR = $products[0];
        Restock::create([
            'product_id' => $semenTR->id,
            'qty_base_unit' => 1500, // 30 sak
            'unit_name_at_restock' => 'sak',
            'cost_price_per_base_unit_at_restock' => 2700, // naik dari 2500
            'location' => 'Gudang Belakang',
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
            // Transaksi 1: Budi jual semen 3 sak + paku 2 kg (6 hari lalu)
            [
                'cashier' => $karyawan1, 'method' => 'tunai', 'discount' => 0, 'days_ago' => 6,
                'items' => [
                    ['product_idx' => 0, 'unit' => 'sak', 'qty' => 3],
                    ['product_idx' => 8, 'unit' => 'kg', 'qty' => 2],
                ],
            ],
            // Transaksi 2: Siti jual besi beton 8mm 1 ikat (5 hari lalu)
            [
                'cashier' => $karyawan2, 'method' => 'qris', 'discount' => 0, 'days_ago' => 5,
                'items' => [
                    ['product_idx' => 2, 'unit' => 'ikat', 'qty' => 1],
                ],
            ],
            // Transaksi 3: Owner jual cat dulux 1 pail + kuas 3 buah, diskon 10.000 (4 hari lalu)
            [
                'cashier' => $owner, 'method' => 'tunai', 'discount' => 10000, 'days_ago' => 4,
                'items' => [
                    ['product_idx' => 6, 'unit' => 'pail', 'qty' => 1],
                    ['product_idx' => 13, 'unit' => 'buah', 'qty' => 3],
                ],
            ],
            // Transaksi 4: Budi jual pipa 1/2" 5 batang (3 hari lalu)
            [
                'cashier' => $karyawan1, 'method' => 'tunai', 'discount' => 0, 'days_ago' => 3,
                'items' => [
                    ['product_idx' => 4, 'unit' => 'batang', 'qty' => 5],
                ],
            ],
            // Transaksi 5: Siti jual seng 3 lembar + triplek 2 lembar (3 hari lalu)
            [
                'cashier' => $karyawan2, 'method' => 'qris', 'discount' => 5000, 'days_ago' => 3,
                'items' => [
                    ['product_idx' => 9, 'unit' => 'lembar', 'qty' => 3],
                    ['product_idx' => 10, 'unit' => 'lembar', 'qty' => 2],
                ],
            ],
            // Transaksi 6: Owner jual semen holcim 5 sak (2 hari lalu)
            [
                'cashier' => $owner, 'method' => 'tunai', 'discount' => 0, 'days_ago' => 2,
                'items' => [
                    ['product_idx' => 1, 'unit' => 'sak', 'qty' => 5],
                ],
            ],
            // Transaksi 7: Budi jual kran air 6 buah + pipa 3/4" 3 batang (2 hari lalu)
            [
                'cashier' => $karyawan1, 'method' => 'tunai', 'discount' => 15000, 'days_ago' => 2,
                'items' => [
                    ['product_idx' => 11, 'unit' => 'buah', 'qty' => 6],
                    ['product_idx' => 5, 'unit' => 'batang', 'qty' => 3],
                ],
            ],
            // Transaksi 8: Siti jual cat jotun 1 galon (1 hari lalu)
            [
                'cashier' => $karyawan2, 'method' => 'qris', 'discount' => 0, 'days_ago' => 1,
                'items' => [
                    ['product_idx' => 7, 'unit' => 'galon', 'qty' => 1],
                ],
            ],
            // Transaksi 9: Budi jual besi 10mm 5 lonjor + kawat 1 roll (1 hari lalu)
            [
                'cashier' => $karyawan1, 'method' => 'tunai', 'discount' => 0, 'days_ago' => 1,
                'items' => [
                    ['product_idx' => 3, 'unit' => 'lonjor', 'qty' => 5],
                    ['product_idx' => 12, 'unit' => 'roll', 'qty' => 1],
                ],
            ],
            // Transaksi 10: Owner jual semen tiga roda 2 sak + bata 20 buah (hari ini)
            [
                'cashier' => $owner, 'method' => 'tunai', 'discount' => 0, 'days_ago' => 0,
                'items' => [
                    ['product_idx' => 0, 'unit' => 'sak', 'qty' => 2],
                    ['product_idx' => 14, 'unit' => 'buah', 'qty' => 20],
                ],
            ],
            // Transaksi 11: Budi jual pipa 1/2" 2 lusin (hari ini)
            [
                'cashier' => $karyawan1, 'method' => 'qris', 'discount' => 20000, 'days_ago' => 0,
                'items' => [
                    ['product_idx' => 4, 'unit' => 'lusin', 'qty' => 2],
                ],
            ],
            // Transaksi 12: Siti jual cat dulux 2 galon + paku 1 dus (hari ini)
            [
                'cashier' => $karyawan2, 'method' => 'tunai', 'discount' => 0, 'days_ago' => 0,
                'items' => [
                    ['product_idx' => 6, 'unit' => 'galon', 'qty' => 2],
                    ['product_idx' => 8, 'unit' => 'dus', 'qty' => 1],
                ],
            ],
        ];

        foreach ($txnData as $tx) {
            $subtotal = 0;
            $itemsForTxn = [];

            foreach ($tx['items'] as $item) {
                $product = $products[$item['product_idx']];
                $unit = ProductUnit::where('product_id', $product->id)
                    ->where('unit_name', $item['unit'])
                    ->first();

                // If unit is the base unit itself
                $conversionFactor = $unit ? $unit->conversion_factor : 1;
                $unitName = $unit ? $unit->unit_name : $product->base_unit;

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

            $transaction = Transaction::create([
                'cashier_user_id' => $tx['cashier']->id,
                'transaction_datetime' => $txnDatetime,
                'payment_method' => $tx['method'],
                'subtotal_before_discount' => $subtotal,
                'discount_amount' => $tx['discount'],
                'total_amount' => $subtotal - $tx['discount'],
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
    }
}
