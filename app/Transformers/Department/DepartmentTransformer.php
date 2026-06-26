<?php

namespace App\Transformers\Department;

use App\Models\Department;
use App\Models\DepartmentStore;
use App\Models\RecipeCategory;
use App\Models\RecipeQuantity;
use App\Models\Role;
use App\Transformers\BaseTransformer;
use App\Transformers\RecipeCategory\RecipeCategoryTransformer;

class DepartmentTransformer extends BaseTransformer
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
    public static function transform(Department $department, $data = [])
    {
        $recipes = $department->recipes()->with('recipeCategory')->get();
        $recipes = $department->recipes()->with('recipeCategory')->where(fn ($query) => $query
            ->where('quantity', '!=', 0)
            ->orwhere('over_quantity', '!=', 0))->get();

        if (! empty($data)) {
            if (isset($data['parent_id'])) {
                $recipes = $recipes->filter(function ($recipe) use ($data) {
                    return $recipe->recipeCategory->category_id == $data['parent_id'];
                });
            }
            if (isset($data['category_id'])) {
                $recipes = $recipes->filter(function ($recipe) use ($data) {
                    return $recipe->recipe_category_id == $data['category_id'];
                });
            }
        }

        if (auth()->user()->roles()->first()->id == Role::SUPPLY_ROLE_ID) {
            $recipes = $recipes->filter(function ($recipe) {
                return $recipe->recipeCategory->category_id == '01jgvw8pc4dygb6zwnbs4gnb73';
            });
        }

        $recipes = $recipes->filter(function ($recipe) use ($department) {
            return $recipe->pivot->department_id == $department->id;
        });

        $recipes = $recipes->groupBy('recipe_category_id');

        $formatedRecipes = [];
        foreach ($recipes as $index => $recipeCategoryGroup) {
            $recipeCategory = RecipeCategory::find($index);
            foreach ($recipeCategoryGroup as $recipe) {
                $formatedRecipes[$recipeCategory->name][] = [
                    'department_store_id' => $recipe->pivot->id,
                    'id' => $recipe->id,
                    'name' => $recipe->name,
                    'image' => $recipe->image ? (string) config('app.url').$recipe->image : '',
                    'quantity' => $recipe->pivot->quantity,
                    'over_quantity' => $recipe->pivot->over_quantity,
                    'under_quantity' => $recipe->pivot->under_quantity,
                    'unit' => $recipe->unit->name,
                    'price' => $recipe->pivot->price,
                    'actual_quantity' => $recipe->pivot->actual_quantity,
                    'recipe_category' => RecipeCategoryTransformer::transform($recipe->recipeCategory),
                    'invoices' => $recipe
                        ->invoices()
                        ->where('to', $department->id)
                        ->get()
                        ->map(function ($invoice) use ($department, $recipe) {
                            $deptStore = DepartmentStore::where('recipe_id', $recipe->id)
                                ->where('department_id', $department->id)
                                ->first();

                            return [
                                ...($invoice->toArray()),
                                'remaining' => RecipeQuantity::where('department_store_id', $deptStore->id)
                                    ->where('invoice_id', $invoice->id)
                                    ->pluck('remaining') ?? 0,
                            ];
                        }),
                    'total_price' => $recipe->pivot->price * $recipe->pivot->quantity,
                ];
            }
        }

        return [
            'id' => (string) $department->id,
            'name' => $department->name,
            'image' => $department->image ? (string) config('app.url').$department->image : '',
            'code' => $department->code,
            'phone' => $department->phone,
            'type' => $department->type,
            'department_store' => $formatedRecipes,
            'created_at' => $department->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $department->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
