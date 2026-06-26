<?php

namespace App\Transformers\Recipe;

use App\Models\RecipeCategory;
use App\Models\Unit;
use App\Transformers\BaseTransformer;
use App\Transformers\RecipeCategory\RecipeCategoryTransformer;

class ExpireRecipesTransformer extends BaseTransformer
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = ['recipeCategory', 'unit'];

    /**
     * List of autoloaded default relations.
     *
     * @var array
     */
    protected $load = [];

    /**
     * Transform the model.
     *
     * @return array
     */
    public static function transform($recipe)
    {

        $unit = Unit::find($recipe['unit_id']);
        $recipe_category_id = RecipeCategory::find($recipe['recipe_category_id']);
        unset($recipe['recipe_category_id']);
        unset($recipe['unit_id']);
        $recipe['unit'] = [
            'id' => $unit->id,
            'name' => $unit->name,
        ];
        $recipe['recipe_category'] = RecipeCategoryTransformer::transform($recipe->recipeCategory);

        return $recipe;
    }
}
