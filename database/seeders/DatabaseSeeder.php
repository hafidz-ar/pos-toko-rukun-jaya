<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Member;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Users
        $owner = User::create([
            'name' => 'Ci Ali',
            'role' => 'owner',
            'pin' => '1111',
        ]);

        $cashier1 = User::create([
            'name' => 'Budi',
            'role' => 'cashier',
            'pin' => '2222',
        ]);

        $cashier2 = User::create([
            'name' => 'Siti',
            'role' => 'cashier',
            'pin' => '3333',
        ]);

        $users = [$owner, $cashier1, $cashier2];

        // 2. Create Locations
        $locations = [
            Location::create(['location_name' => 'Rak A1', 'description' => 'Rak pipa dan plastik depan']),
            Location::create(['location_name' => 'Rak A2', 'description' => 'Rak paku dan perkakas']),
            Location::create(['location_name' => 'Rak B1', 'description' => 'Rak cat tembok']),
            Location::create(['location_name' => 'Rak B2', 'description' => 'Rak fittings pipa']),
            Location::create(['location_name' => 'Gudang Belakang', 'description' => 'Penyimpanan semen dan besi beton']),
            Location::create(['location_name' => 'Etalase Depan', 'description' => 'Barang kecil/aksesoris kuningan']),
        ];

        // 3. Create Members
        $members = Member::factory(5)->create();

        // 4. Create Products and Variants
        $productNames = [
            'Pipa Paralon Maspion AW' => ['1/2 Inch', '3/4 Inch', '1 Inch'],
            'Semen Tiga Roda 40kg' => ['Standard'],
            'Cat Tembok Dulux Catylac Putih' => ['5 Kg', '25 Kg'],
            'Kawat Beton / Bendrat' => ['1 Roll'],
            'Paku Kayu' => ['2 Inch', '3 Inch', '4 Inch'],
            'Seng Gelombang' => ['1.8 Meter', '2.4 Meter'],
            'Kran Air Stainless Bulat' => ['1/2 Inch', '3/4 Inch'],
            'Besi Beton SNI' => ['8mm', '10mm', '12mm'],
            'Kuas Cat Eterna' => ['2 Inch', '3 Inch', '4 Inch'],
            'Double Tape Busa 3M' => ['Standard'],
        ];

        $variants = [];
        $invoiceCounter = 1;

        foreach ($productNames as $pName => $sizes) {
            $product = Product::create([
                'name' => $pName,
                'description' => "Katalog barang untuk " . $pName,
            ]);

            foreach ($sizes as $idx => $size) {
                // Calculate realistic prices (in Rupiah)
                $purchasePrice = rand(5, 150) * 1000; // e.g. Rp 5.000 to Rp 150.000
                $sellingPrice = round($purchasePrice * rand(115, 135) / 100, -2); // 15-35% markup, rounded to nearest 100 Rp

                // Select location index based on category
                $locIndex = 0;
                if (str_contains($pName, 'Pipa')) {
                    $locIndex = 0;
                } elseif (str_contains($pName, 'Paku') || str_contains($pName, 'Kuas')) {
                    $locIndex = 1;
                } elseif (str_contains($pName, 'Cat')) {
                    $locIndex = 2;
                } elseif (str_contains($pName, 'Semen') || str_contains($pName, 'Besi')) {
                    $locIndex = 4;
                } else {
                    $locIndex = 5;
                }

                $location = $locations[$locIndex];
                $initialStock = rand(20, 100);

                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'size' => $size,
                    'sku' => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $pName), 0, 4)) . '-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $size), 0, 3)) . '-' . rand(10, 99),
                    'barcode' => '899' . sprintf('%010d', rand(1, 9999999999)),
                    'purchase_price' => $purchasePrice,
                    'selling_price' => $sellingPrice,
                    'stock' => $initialStock,
                    'min_stock_level' => rand(5, 10),
                    'location_id' => $location->id,
                ]);

                $variants[] = $variant;

                // Log initial stock movement (in)
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $owner->id,
                    'movement_type' => 'in',
                    'quantity' => $initialStock,
                    'notes' => 'Stok awal barang masuk dari kulakan',
                ]);
            }
        }

        // 5. Create Transactions
        for ($t = 1; $t <= 15; $t++) {
            $user = $users[array_rand($users)];
            $member = rand(0, 1) ? $members->random() : null;

            // Choose 1-4 random variants to sell
            $totalCost = 0;
            $totalRevenue = 0;
            $itemsToSave = [];

            // Get random count of items for this transaction
            $itemCount = rand(1, 4);
            $chosenKeys = array_rand($variants, $itemCount);
            
            // Normalize keys if array_rand returned a single integer
            $chosenVariantKeys = is_array($chosenKeys) ? $chosenKeys : [$chosenKeys];

            foreach ($chosenVariantKeys as $variantIndex) {
                $variant = $variants[$variantIndex];
                $quantity = rand(1, 5);

                $totalCost += $variant->purchase_price * $quantity;
                $totalRevenue += $variant->selling_price * $quantity;

                $itemsToSave[] = [
                    'product_id' => $variant->product_id,
                    'quantity' => $quantity,
                    'selling_price' => $variant->selling_price,
                    'purchase_price' => $variant->purchase_price,
                    'variant' => $variant,
                ];
            }

            // Apply a small discount occasionally if member
            $discount = 0;
            if ($member && rand(0, 1)) {
                $discount = round(($totalRevenue * 0.05), -2); // 5% discount
            }

            $invoiceNumber = 'INV-' . date('Ymd') . '-' . sprintf('%04d', $invoiceCounter++);

            $transaction = Transaction::create([
                'invoice_number' => $invoiceNumber,
                'user_id' => $user->id,
                'member_id' => $member?->id,
                'total_revenue' => $totalRevenue - $discount,
                'total_cost' => $totalCost,
                'discount_applied' => $discount,
                'status' => 'completed',
            ]);

            // Set custom created_at timestamp backdated slightly for realistic reporting
            $transaction->created_at = now()->subDays(rand(0, 7))->subHours(rand(0, 10));
            $transaction->save();

            foreach ($itemsToSave as $item) {
                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'selling_price' => $item['selling_price'],
                    'purchase_price' => $item['purchase_price'],
                ]);

                // Update variant stock
                $v = $item['variant'];
                $v->stock = max(0, $v->stock - $item['quantity']);
                $v->save();

                // Log stock movement (out)
                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'user_id' => $user->id,
                    'movement_type' => 'out',
                    'quantity' => $item['quantity'],
                    'notes' => "Terjual via transaksi {$invoiceNumber}",
                    'created_at' => $transaction->created_at,
                ]);
            }
        }
    }
}
