<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $materials = [
            'Pipa Paralon Maspion AW',
            'Semen Tiga Roda 40kg',
            'Cat Tembok Dulux Catylac Putih',
            'Kawat Beton / Kawat Bendrat',
            'Paku Kayu 2 Inch',
            'Seng Gelombang 1.8m',
            'Kran Air Stainless Bulat',
            'Besi Beton 10mm Sni',
            'Kuas Cat Eterna 3 Inch',
            'Double Tape Busa 3M',
        ];

        return [
            'name' => fake()->unique()->randomElement($materials) ?? fake()->words(3, true),
            'description' => fake()->paragraph(),
        ];
    }
}
