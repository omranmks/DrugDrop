<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DrugDetail>
 */
class DrugDetailFactory extends Factory
{
    private static $order = 47;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'drug_id' => self::$order++,
            'trade_name' => fake()->word() . ' ' . fake()->word() . rand(1, 9999),
            'scientific_name' => fake()->word(),
            'company' => fake()->company(),
            'dose_unit' => fake()->randomElement(['ml', 'mg']),
            'description' => fake()->text(),
            'lang_code' => 'en',
        ];
    }
}
