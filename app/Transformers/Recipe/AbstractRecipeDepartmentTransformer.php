<?php

namespace App\Transformers\Recipe;

use App\Models\DepartmentStore;
use App\Transformers\BaseTransformer;

class AbstractRecipeDepartmentTransformer extends BaseTransformer
{
    protected $load = [];

    public static function transform(DepartmentStore $recipe)
    {
        $RecipeTransformer = new AbstractRecipeTransformer;

        return [
            'id' => (string) $recipe->id,
            'department_id' => $recipe->department_id,
            'quantity' => $recipe->quantity,
            'price' => $recipe->price,
            'recipe' => AbstractRecipeTransformer::transform($recipe->recipe),

        ];

    }
}
