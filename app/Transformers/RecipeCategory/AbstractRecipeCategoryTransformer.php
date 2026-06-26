<?php

namespace App\Transformers\RecipeCategory;

use App\Models\RecipeCategory;
use App\Transformers\BaseTransformer;

class AbstractRecipeCategoryTransformer extends BaseTransformer
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
     * @return array
     */
    public static function transform(RecipeCategory $recipeCategory)
    {
        return [
            'id' => (string) $recipeCategory->id,
            'name' => $recipeCategory->name,
            'description' => $recipeCategory->description,
            'image' => (string) config('app.url').$recipeCategory->image,
            'parent' => $recipeCategory->category->name,
            'parent_id' => $recipeCategory->category->id,

        ];
    }
}
