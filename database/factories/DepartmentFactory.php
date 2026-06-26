<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Department>
 */
class DepartmentFactory extends Factory
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
            'image' => $this->faker->imageUrl(),
            'code' => 'INV-'.$this->faker->unique()->randomNumber(8),
            'phone' => $this->faker->phoneNumber,
            'type' => $this->faker->randomElement(['source', 'reciver', 'both']),
        ];
    }
}
