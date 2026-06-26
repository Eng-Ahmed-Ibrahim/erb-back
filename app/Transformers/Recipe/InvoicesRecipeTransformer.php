<?php

namespace App\Transformers\Recipe;

use App\Models\Recipe;
use App\Transformers\BaseTransformer;
use App\Transformers\RecipeCategory\RecipeCategoryTransformer;

class InvoicesRecipeTransformer extends BaseTransformer
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = [];

    /**
     * List of autoloaded default relations.
     *
     * @var array
     */
    protected $load = [];

    /**
     * Transform the model.
     *
     * @param  \App\Models\Recipe  $recipe
     * @return array
     */
    public static function transform($data)
    {
        $recipe = Recipe::find($data['recipe_id']);
        if ($recipe) {
            $data['recipe_category'] = RecipeCategoryTransformer::transform($recipe->recipeCategory);
        }

        return [
            'id' => $data['recipe_id'],
            'name' => $data['name'],
            'image' => (string) config('app.url').$data['image'],
            'minimum_limt' => $data['minimum_limt'],
            'total_quantity' => $data['total_quantity'],
            'total_price' => $data['total_price'],
            'recipe_category' => $data['recipe_category'],
            // 'recipe_category_parent' => RecipeCategoryParentTransformer::transform($recipe->recipeCategory->category),

        ];

    }
}
