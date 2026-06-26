<?php

namespace App\Transformers\Store;

use App\Models\Recipe;
use App\Transformers\BaseTransformer;
use App\Transformers\RecipeCategory\RecipeCategoryTransformer;
use Illuminate\Support\Facades\DB;

class StoreTransformer extends BaseTransformer
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = ['departments'];

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
        $totalQuantity = DB::table('department_store')
            ->where('recipe_id', $recipe->id)
            ->sum('quantity');

        $totalPrice = DB::table('department_store')
            ->where('recipe_id', $recipe->id)
            ->sum('price');

        if (! ($totalQuantity > 0)) {
            return [];
        }

        return [
            'id' => $recipe->id,
            'name' => $recipe->name,
            'image' => (string) config('app.url').$recipe->image ? (string) config('app.url').$recipe->image : '',
            'unit' => [
                'id' => $recipe->unit->id,
                'name' => $recipe->unit->name,
            ],
            'category' => $recipe->recipeCategory->category->name,
            'sub_category' => $recipe->recipeCategory->name,
            'recipe_category' => RecipeCategoryTransformer::transform($recipe->recipeCategory),
            'quantity' => $totalQuantity,
            'price' => $totalPrice,
        ];
    }
}
