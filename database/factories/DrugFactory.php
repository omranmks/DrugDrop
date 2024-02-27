<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Drug>
 */
class DrugFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tag_id' => fake()->numberBetween(1, 7),
            'dose' => fake()->numberBetween(100, 500),
            'quantity' => fake()->numberBetween(1, 100),
            'img_url' => null,
            'price' => fake()->numberBetween(1000, 15000),
            'expiry_date' => fake()->date('2025-11-15'),
        ];
    }
}
