<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Wrap everything in a database transaction
        DB::transaction(function () {
            // 1. Pre-validation: check for empty or invalid values in products.base_unit and product_units.unit_name
            $invalidProducts = DB::table('products')
                ->whereNull('base_unit')
                ->orWhere('base_unit', '')
                ->count();

            $invalidProductUnits = DB::table('product_units')
                ->whereNull('unit_name')
                ->orWhere('unit_name', '')
                ->count();

            if ($invalidProducts > 0 || $invalidProductUnits > 0) {
                $errorMsg = "Migration aborted: Found {$invalidProducts} products and {$invalidProductUnits} product_units with empty or invalid unit name. Please clean up legacy data first.";
                Log::error($errorMsg);
                throw new \Exception($errorMsg);
            }

            // 2. Extract and populate units table from categories.units
            $categories = DB::table('categories')->get();
            $insertedUnits = [];

            foreach ($categories as $cat) {
                if (!empty($cat->units)) {
                    $unitsArray = explode(',', $cat->units);
                    foreach ($unitsArray as $unitName) {
                        $normalized = trim(strtolower($unitName));
                        if (!empty($normalized) && !in_array($normalized, $insertedUnits)) {
                            $insertedUnits[] = $normalized;
                        }
                    }
                }
            }

            // Also extract from products.base_unit
            $products = DB::table('products')->get();
            foreach ($products as $prod) {
                if (!empty($prod->base_unit)) {
                    $normalized = trim(strtolower($prod->base_unit));
                    if (!empty($normalized) && !in_array($normalized, $insertedUnits)) {
                        $insertedUnits[] = $normalized;
                    }
                }
            }

            // Also extract from product_units.unit_name
            $prodUnits = DB::table('product_units')->get();
            foreach ($prodUnits as $pu) {
                if (!empty($pu->unit_name)) {
                    $normalized = trim(strtolower($pu->unit_name));
                    if (!empty($normalized) && !in_array($normalized, $insertedUnits)) {
                        $insertedUnits[] = $normalized;
                    }
                }
            }

            // Insert unique units
            foreach ($insertedUnits as $name) {
                // For symbol, default to name itself
                DB::table('units')->insertOrIgnore([
                    'name' => $name,
                    'symbol' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Fetch all units to map by name
            $unitMap = DB::table('units')->pluck('id', 'name')->toArray();

            // 3. Map base_unit_id in products
            $mappedProducts = 0;
            foreach ($products as $prod) {
                $normalized = trim(strtolower($prod->base_unit));
                if (isset($unitMap[$normalized])) {
                    DB::table('products')
                        ->where('id', $prod->id)
                        ->update(['base_unit_id' => $unitMap[$normalized]]);
                    $mappedProducts++;
                } else {
                    $errorMsg = "Migration failed: Could not map product '{$prod->name}' (ID: {$prod->id}) with base unit '{$prod->base_unit}'.";
                    Log::error($errorMsg);
                    throw new \Exception($errorMsg);
                }
            }

            // 4. Map unit_id in product_units
            $mappedProductUnits = 0;
            foreach ($prodUnits as $pu) {
                $normalized = trim(strtolower($pu->unit_name));
                if (isset($unitMap[$normalized])) {
                    DB::table('product_units')
                        ->where('id', $pu->id)
                        ->update(['unit_id' => $unitMap[$normalized]]);
                    $mappedProductUnits++;
                } else {
                    $errorMsg = "Migration failed: Could not map product unit ID: {$pu->id} with name '{$pu->unit_name}'.";
                    Log::error($errorMsg);
                    throw new \Exception($errorMsg);
                }
            }

            Log::info("Migration ETL complete. Mapped {$mappedProducts} products and {$mappedProductUnits} product_units.");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('products')->update(['base_unit_id' => null]);
        DB::table('product_units')->update(['unit_id' => null]);
    }
};
