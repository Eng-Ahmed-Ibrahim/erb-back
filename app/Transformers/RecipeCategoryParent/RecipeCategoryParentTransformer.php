<?php

namespace App\Transformers\RecipeCategoryParent;

use App\Models\RecipeParentCategory;
use App\Transformers\BaseTransformer;

class RecipeCategoryParentTransformer extends BaseTransformer
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
     * @param  \App\Models\RecipeCategory  $recipeCategory
     * @return array
     */
    public static function transform(RecipeParentCategory $recipeCategory)
    {
        return [
            'id' => (string) $recipeCategory->id,
            'name' => $recipeCategory->name,
            'description' => $recipeCategory->description,
            'image' => (string) config('app.url').$recipeCategory->image,
            'subCategories' => self::formatMany($recipeCategory->subCategories, 'App\Transformers\RecipeCategory\RecipeCategoryTransformer'),
        ];
    }
}
