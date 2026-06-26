<?php

namespace Database\Factories;

use App\Models\RecipeCategory;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Recipe>
 */
class RecipeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitId = Unit::all()->random()->id;
        $recipeCategoryId = RecipeCategory::all()->random()->id;

        return [
            'name' => $this->faker->name,
            'image' => $this->faker->imageUrl(),
            'minimum_limt' => $this->faker->randomNumber(8),
            'days_before_expire' => $this->faker->randomNumber(2),
            'recipe_category_id' => $recipeCategoryId,
            'unit_id' => $unitId,
        ];
    }
}
