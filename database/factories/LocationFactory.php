<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_name' => fake()->randomElement(['Rak A1', 'Rak A2', 'Rak B1', 'Rak B2', 'Gudang Belakang', 'Etalase Depan', 'Rak C1']),
            'description' => fake()->sentence(),
        ];
    }
}
