<?php

namespace App\Transformers\Supplier;

use App\Models\RecipeParentCategory;
use Carbon\Carbon;
use Flugg\Responder\Transformers\Transformer;

class ShowSupplierTransformer extends Transformer
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
     * @param  \App\Models\ShowSupplier  $showSupplier
     * @return array
     */
    public static function transform($suppliers, $data = [])
    {
        return $suppliers->map(function ($supplier) use ($data) {
            $invoices_price = $supplier
                ->invoices()
                ->join('invoice_recipe', 'invoices.id', '=', 'invoice_recipe.invoice_id')
                ->join('recipes', 'recipes.id', '=', 'invoice_recipe.recipe_id')
                ->join('recipe_categories', 'recipe_categories.id', '=', 'recipes.recipe_category_id')
                ->when(isset($data['warehouse_section_id']), fn($query) => $query
                    ->whereIN('recipe_categories.category_id', RecipeParentCategory::where('warehouse_section_id', $data['warehouse_section_id'])->pluck('id')->toArray()))
                ->when(isset($data['to_date']), fn($query) => $query
                    ->where('invoices.created_at', '<=', Carbon::parse($data['to_date'])->endofDay()))
                ->when(isset($data['from_date']), fn($query) => $query
                    ->where('invoices.created_at', '>=', $data['from_date']))
                ->Select('invoice_recipe.total_price')
                ->get()
                ->sum('total_price');

            // if ($invoices_price != 0) {
                return [
                    'id' => (string) $supplier->id,
                    'name' => $supplier->name,
                    'phone' => $supplier->phone,
                    'type' => $supplier->type,
                    'address' => $supplier->address,
                    'total_invoices_price' => number_format($invoices_price, 3, '.', ''),
                ];
            // }
        });
        // ->filter();
    }
}
