<?php

namespace App\Transformers\RecipeCategoryParent;

use App\Models\RecipeParentCategory;
use App\Transformers\BaseTransformer;
use Illuminate\Support\Facades\Log;

class AbstractRecipeCategoryParentTransformer extends BaseTransformer
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
    public static function transform($recipeCategory)
    {
        if (empty($recipeCategory)) {
            return [];
        }
        Log::info('app url ', [config('app.url')]);
        if ($recipeCategory instanceof RecipeParentCategory) {
            return [
                'id' => (string) $recipeCategory->id,
                'name' => $recipeCategory->name,
                'description' => $recipeCategory->description,
                'image' => (string) config('app.url') . $recipeCategory->image,
            ];
        }

        if (is_array($recipeCategory)) {
            return [
                'id' => (string) ($recipeCategory['id'] ?? ''),
                'name' => $recipeCategory['name'] ?? '',
                'description' => $recipeCategory['description'] ?? '',
                'image' => (string)config('app.url') . ($recipeCategory['image'] ?? ''),
            ];
        }
    }
}
