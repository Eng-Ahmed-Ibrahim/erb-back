<?php

namespace App\Transformers\Recipe;

use App\Models\InvoiceRecipe;
use App\Models\Recipe;
use App\Transformers\BaseTransformer;
use Carbon\Carbon;
use App\Models\DepartmentStore;
use App\Models\Department;
use Illuminate\Support\Facades\DB;

class AbstractRecipeTransformer extends BaseTransformer
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
    public static function transform(Recipe $recipe)
    {
        $invoice = InvoiceRecipe::where('recipe_id', $recipe->id)->orderBy('created_at', 'desc')->first();

        $department = auth('api')->user()->department;
        $pivotId = DB::table('department_store')
            ->where('recipe_id', $recipe->id)
            ->where('department_id', $department->id)
            ->get();

        $quantities = collect();
        if (count($pivotId) > 0) {
            $pivotId = $pivotId[0]->id;
            $quantities = DB::table('recipe_quantities')
                ->where('recipe_id', $recipe->id)
                ->where('department_store_id', $pivotId)
                ->where('remaining', '>', 0)
                ->get();
        }

        $sourceQuantity = DepartmentStore::where('recipe_id', $recipe->id)
            ->where('department_id', Department::query()->where('type', 'source')
                ->first()->id)
            ->first()?->quantity ?? 0;

        return [
            'id' => (string) $recipe->id,
            'name' => $recipe->name,
            'image' => (string) config('app.url') . $recipe->image,
            'recipe_category_name' => $recipe?->recipeCategory?->name,
            'recipe_category_parent_name' => $recipe?->recipeCategory?->category?->name,
            'unit' => [
                'id' => $recipe->unit->id,
                'name' => $recipe->unit->name,
            ],
            'quantity' => $sourceQuantity,
            'minimum_limt' => $recipe->minimum_limt,
            'days_before_expire' => $recipe->days_before_expire,
            'last_recipe_invoice' => [
                'price' => $invoice?->price ?? 0,
                'quantity' => $invoice?->quantity ?? 0,
            ],
            'status' => $recipe->status,
            'created_at' => Carbon::parse($recipe->created_at)->format('Y-m-d'),
            'has_products' => $recipe->products->count() > 0,
        ];
    }
}
