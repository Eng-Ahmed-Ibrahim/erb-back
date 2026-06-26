<?php

namespace App\Transformers\Recipe;

use App\Models\Department;
use App\Models\Recipe;
use App\Transformers\RecipeCategory\RecipeCategoryTransformer;
use App\Transformers\BaseTransformer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecipeTransformer extends BaseTransformer
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
    public static function transform(Recipe $recipe, Department $department)
    {
        $formatedQuantities = [];
        $recipePivot = $department->recipes()->wherePivot('recipe_id', $recipe->id)?->first()?->pivot;
        $totalQuantity = $recipePivot?->quantity ?? 0;
        $price = $recipePivot?->price ?? 0;

        $pivotId = DB::table('department_store')
            ->where('recipe_id', $recipe->id)
            ->where('department_id', $department->id)
            ->get();

        if (count($pivotId) > 0) {
            $pivotId = $pivotId[0]->id;
            $quantities = DB::table('recipe_quantities')
                ->join('invoices', 'invoices.id', '=', 'recipe_quantities.invoice_id')
                ->where('invoices.status', '=', 'approved')
                ->where('recipe_id', $recipe->id)
                ->where('department_store_id', $pivotId)
                ->where('remaining', '>', 0)
                ->get();
        } else {
            $quantities = [];
        }

        foreach ($quantities as $quantity) {
            $formatedQuantities[] = self::formatQuantity($quantity);
        }

        return [
            'id' => (string) $recipe->id,
            'name' => $recipe->name,
            'image' => (string) config('app.url') . $recipe->image,
            'recipe_category' => RecipeCategoryTransformer::transform($recipe->recipeCategory),
            // 'recipe_category_parent' => RecipeCategoryParentTransformer::transform($recipe->recipeCategory->category),
            'unit' => [
                'id' => $recipe->unit->id,
                'name' => $recipe->unit->name,
            ],
            'minimum_limt' => $recipe->minimum_limt,
            'days_before_expire' => $recipe->days_before_expire,
            'total_quantity' => $totalQuantity,
            'price' => $price,
            'quantitesDetails' => $formatedQuantities,
        ];
    }

    private static function formatQuantity($quantity)
    {
        return [
            'price' => $quantity->price,
            'quantity' => $quantity->remaining,
            'expire_date' => $quantity->expire_date,
            'total_price' => $quantity->price * $quantity->remaining,
            'invoice_id' => $quantity->invoice_id,
            'invoice_code' =>  $quantity->code,
            'incoming_quantity' => $quantity->quantity,
        ];
    }
}
