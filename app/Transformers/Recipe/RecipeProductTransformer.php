<?php

namespace App\Transformers\Recipe;

use App\Models\Recipe;
use App\Transformers\BaseTransformer;
use App\Transformers\RecipeCategory\RecipeCategoryTransformer;

class RecipeProductTransformer extends BaseTransformer
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = ['recipeCategory', 'unit', 'invoices'];

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
    public static function transform(Recipe $recipe)
    {

        return [
            'id' => (string) $recipe->id,
            'name' => $recipe->name,
            'image' => (string) config('app.url').$recipe->image,
            'type' => $recipe->recipeCategory->name,
            'unit' => $recipe->unit->name,
            'quantity' => $recipe->pivot->quantity,
            'recipe_category' => RecipeCategoryTransformer::transform($recipe->recipeCategory),
            // 'recipe_category_parent' => RecipeCategoryParentTransformer::transform($recipe->recipeCategory->category),
        ];
    }
}
