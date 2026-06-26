<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        return [
            'from' => Department::all()->random()->id,
            'to' => Department::all()->random()->id,
            'supplier_id' => Supplier::all()->random()->id,
            'invoice_date' => $this->faker->date(),
            'code' => $this->faker->unique()->randomNumber(8),
            'image' => $this->faker->imageUrl(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'type' => $this->faker->randomElement(['out_going', 'in_coming', 'returned']),
            'invoice_price' => $this->faker->randomFloat(2, 0, 999999),
            'discount' => $this->faker->randomFloat(2, 0, 999999),
            'tax' => $this->faker->randomFloat(2, 0, 999999),
            'total_price' => $this->faker->randomFloat(2, 0, 999999),
            'note' => $this->faker->text(),
        ];
    }
}
