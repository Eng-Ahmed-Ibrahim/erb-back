<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
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
        $subCategoryId = SubCategory::all()->random()->id;
        $categoryId = Category::all()->random()->id;

        return [
            'name' => $this->faker->name,
            'image' => $this->faker->imageUrl(),
            'offer' => $this->faker->randomFloat(2, 0, 999999),
            'category_id' => $categoryId,
            'sub_category_id' => $subCategoryId,
        ];
    }
}
