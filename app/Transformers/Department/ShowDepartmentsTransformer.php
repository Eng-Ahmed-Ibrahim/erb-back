<?php

namespace App\Transformers\Department;

use App\Models\RecipeParentCategory;
use Carbon\Carbon;
use Flugg\Responder\Transformers\Transformer;
use Illuminate\Support\Facades\Log;

class ShowDepartmentsTransformer extends Transformer
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
     * @param  \App\Models\ShowDepartments  $showDepartments
     * @return array
     */
    public static function transform($departments, $data = [])
    {
        return $departments->map(function ($department) use ($data) {
            if (isset($data['include_invoices']) && $data['include_invoices'] == "false") {
                $invoices_price = 0.001;
            } else {
                $to_invoices_price = $department
                    ->toInvoices()
                    ->join('invoice_recipe', 'invoices.id', '=', 'invoice_recipe.invoice_id')
                    ->join('recipes', 'recipes.id', '=', 'invoice_recipe.recipe_id')
                    ->join('recipe_categories', 'recipe_categories.id', '=', 'recipes.recipe_category_id')
                    ->where('invoices.type', '!=', 'inventory_adjustment')
                    ->when(isset($data['warehouse_section_id']), fn($query) => $query
                        ->whereIN('recipe_categories.category_id', RecipeParentCategory::where('warehouse_section_id', $data['warehouse_section_id'])->pluck('id')->toArray()))
                    ->when(isset($data['to']), fn($query) => $query
                        ->where('invoices.created_at', '<=', Carbon::parse($data['to'])->endofDay()))
                    ->when(isset($data['from']), fn($query) => $query
                        ->where('invoices.created_at', '>=', $data['from']))
                    ->Select('invoice_recipe.total_price')
                    ->get()
                    ->sum('total_price');

                $from_invoices_price = $department
                    ->fromInvoices()
                    ->join('invoice_recipe', 'invoices.id', '=', 'invoice_recipe.invoice_id')
                    ->join('recipes', 'recipes.id', '=', 'invoice_recipe.recipe_id')
                    ->join('recipe_categories', 'recipe_categories.id', '=', 'recipes.recipe_category_id')
                    ->whereNot('invoices.type', 'tainted')
                    ->where('invoices.type', '!=', 'inventory_adjustment')
                    ->when(isset($data['warehouse_section_id']), fn($query) => $query
                        ->whereIN('recipe_categories.category_id', RecipeParentCategory::where('warehouse_section_id', $data['warehouse_section_id'])->pluck('id')->toArray()))
                    ->when(isset($data['to']), fn($query) => $query
                        ->where('invoices.created_at', '<=', Carbon::parse($data['to'])->endofDay()))
                    ->when(isset($data['from']), fn($query) => $query
                        ->where('invoices.created_at', '>=', $data['from']))
                    ->Select('invoice_recipe.total_price')
                    ->get()
                    ->sum('total_price');

                $invoices_price = $department?->id == '01hy3km07mf7fafqn2j6388d1t' ? 0.0001 : $to_invoices_price - $from_invoices_price;
            }
            // if ($invoices_price != 0) {
            return [
                'id' => (string) $department->id,
                'name' => $department->name,
                // 'image' => $department->image ? (string) config('app.url') . $department->image : '',
                'code' => $department->code,
                'phone' => $department->phone,
                'type' => $department->type,
                'total_invoices_price' => number_format($invoices_price, 3, '.', ''),
            ];
            // }
        });
        // ->filter();
    }
}

//
