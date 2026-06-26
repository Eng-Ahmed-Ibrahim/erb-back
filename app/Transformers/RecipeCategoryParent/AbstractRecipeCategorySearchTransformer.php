<?php

namespace App\Transformers\RecipeCategoryParent;

use Flugg\Responder\Transformers\Transformer;

class AbstractRecipeCategorySearchTransformer extends Transformer
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
     * @param  \App\Models\RecipeCategory  $recipeCategory
     * @return array
     */
    public static function transform(array $recipeCategory)
    {
        return [
            'id' => (string) $recipeCategory['id'],
            'name' => $recipeCategory['name'],
            'description' => $recipeCategory['description'],
            'image' => (string) config('app.url').$recipeCategory['image'],
        ];
    }
}
