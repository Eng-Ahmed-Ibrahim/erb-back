<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Price>
 */
class PriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        return [
            'name' => $this->faker->name,
            'price' => $this->faker->randomFloat(2, 0, 999999),
            'default' => $this->faker->randomElement([0, 1]),
            'product_id' => Product::all()->random()->id,
        ];
    }
}
