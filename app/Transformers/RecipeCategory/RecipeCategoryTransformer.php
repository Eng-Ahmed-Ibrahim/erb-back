<?php

namespace App\Transformers\RecipeCategory;

use App\Models\RecipeCategory;
use App\Transformers\BaseTransformer;
use App\Transformers\Recipe\RecipeTransformer;

class RecipeCategoryTransformer extends BaseTransformer
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = ['recipes'];

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
    public static function transform(RecipeCategory $recipeCategory)
    {
        return [
            'id' => (string) $recipeCategory->id,
            'name' => $recipeCategory->name,
            'description' => $recipeCategory->description,
            'image' => (string) config('app.url').$recipeCategory->image,
            // 'recipes' => self::formatMany($recipeCategory->recipes, RecipeTransformer::class),
            'parent' => $recipeCategory->category->name,
            'parent_id' => (string) $recipeCategory->category->id,
        ];
    }
}
