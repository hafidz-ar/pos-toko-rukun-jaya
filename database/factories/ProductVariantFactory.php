<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $purchasePrice = fake()->randomFloat(2, 5000, 150000);
        // Calculate a selling price with 15% to 35% markup, rounded to the nearest 100 Rupiah
        $sellingPrice = round($purchasePrice * fake()->randomFloat(2, 1.15, 1.35), -2);

        return [
            'product_id' => Product::factory(),
            'size' => fake()->randomElement(['1/2 Inch', '3/4 Inch', '1 Inch', 'M', 'L', 'Standard']),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####-??')),
            'barcode' => fake()->unique()->numerify('899#########'), // Standard Indonesian barcode prefix (899)
            'purchase_price' => $purchasePrice,
            'selling_price' => $sellingPrice,
            'stock' => fake()->numberBetween(0, 100),
            'min_stock_level' => fake()->numberBetween(3, 10),
            'location_id' => Location::factory(),
        ];
    }
}
