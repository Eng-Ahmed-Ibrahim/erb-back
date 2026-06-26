<?php

namespace Database\Factories;

use App\Models\RecipeParentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecipeCategory>
 */
class RecipeCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $id = RecipeParentCategory::all()->random()->id;

        return [
            'name' => $this->faker->name,
            'description' => $this->faker->sentence,
            'image' => $this->faker->imageUrl(),
            'category_id' => $id,
        ];
    }
}
