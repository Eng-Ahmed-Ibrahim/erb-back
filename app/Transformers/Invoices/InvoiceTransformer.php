<?php

namespace App\Transformers\Invoices;

use App\Models\Invoice;
use App\Transformers\BaseTransformer;
use App\Transformers\RecipeCategory\RecipeCategoryTransformer;
use Carbon\Carbon;

class InvoiceTransformer extends BaseTransformer
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = ['recipes', 'fromDepartment', 'toDepartment', 'supplier'];

    /**
     * List of autoloaded default relations.
     *
     * @var array
     */
    protected $load = [];

    /**
     * Transform the model.
     *
     * @param  \App\Models\Product  $product
     * @return array
     */
    public static function transform(Invoice $invoice)
    {
        $recipes = $invoice->recipes;

        $formatedPivotsRecipes = [];

        foreach ($recipes as $recipe) {
            $formatedPivotsRecipes[] = [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'image' => (string) config('app.url').$recipe->image,
                'minimum_limit' => $recipe->minimum_limit,
                'quantity' => $recipe->pivot->quantity,
                'price' => $recipe->pivot->price,
                'expire_date' => $recipe->pivot->expire_date,
                'discount' => $recipe->pivot->discount,
                'sub_category' => $recipe->recipeCategory->name,
                'category' => $recipe->recipeCategory->category->name,
                'recipe_category' => RecipeCategoryTransformer::transform($recipe->recipeCategory),
                'total_price' => $recipe->pivot->price * $recipe->pivot->quantity - ($recipe->pivot->discount ?? 0),
                'unit' => [
                    'id' => $recipe->unit->id,
                    'name' => $recipe->unit->name,
                ],
            ];
        }

        return [
            'id' => (string) $invoice->id,
            'from' => [
                'id' => $invoice->fromDepartment?->id,
                'name' => $invoice->fromDepartment?->name,
                'code' => $invoice->fromDepartment?->code,
                'phone' => $invoice->fromDepartment?->phone,
            ],
            'to' => [
                'id' => $invoice->toDepartment?->id,
                'name' => $invoice->toDepartment?->name,
                'code' => $invoice->toDepartment?->code,
                'phone' => $invoice->toDepartment?->phone,
            ],
            'supplier' => [
                'id' => $invoice->supplier?->id,
                'name' => $invoice->supplier?->name,
            ],
            'created_by' => [
                'id' => $invoice->createdBy?->id,
                'name' => $invoice->createdBy?->name,
            ],
            'code' => $invoice->code,
            'invoice_date' => Carbon::parse($invoice->invoice_date)->format('Y-m-d'),
            'status' => $invoice->status,
            'invoice_price' => $invoice->invoice_price,
            'total_price' => $invoice->total_price,
            'image' => (string) config('app.url').$invoice->image,
            'type' => $invoice->type,
            'discount' => $invoice->discount,
            'tax' => $invoice->tax,
            'note' => $invoice->note,
            'recipes' => $formatedPivotsRecipes,
            'registration_date' => $invoice->created_at->format('Y-m-d'),
            'updated_at' => $invoice->updated_at->format('Y-m-d'),
            // 'created_at' => $invoice->created_at->format('Y-m-d H:m:s') .'/n تاريخ إدخال',
            'created_at' => __('تاريخ الإدخال').': '.$invoice->created_at->format('Y-m-d H:i:s').
                ' || '.__('تاريخ الفاتورة').' : '.Carbon::parse($invoice->invoice_date)->format('Y-m-d'),

        ];
    }
}
