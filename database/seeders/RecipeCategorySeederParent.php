<?php

namespace Database\Seeders;

use App\Models\RecipeParentCategory;
use Illuminate\Database\Seeder;

class RecipeCategorySeederParent extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        RecipeParentCategory::factory()->count(100)->create();
    }
}
