<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubCategory>
 */
class SubCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // get the category id from the category table
        return [
            'name' => $this->faker->name,
            'description' => $this->faker->sentence,
            'image' => $this->faker->imageUrl(),
            'category_id' => function () {
                return Category::factory()->create()->id;
            },
        ];
    }
}
