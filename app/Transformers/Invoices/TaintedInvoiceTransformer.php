<?php

namespace App\Transformers\Invoices;

use App\Models\Invoice;
use App\Transformers\BaseTransformer;
use Carbon\Carbon;

class TaintedInvoiceTransformer extends BaseTransformer
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = ['recipes', 'fromDepartment'];

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
    public static function transform(Invoice $invoice)
    {
        $recipes = $invoice->recipes;

        $formatedPivotsRecipes = [];

        foreach ($recipes as $recipe) {
            $formatedPivotsRecipes[] = [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'minimum_limt' => $recipe->minimum_limt,
                'quantity' => $recipe->pivot->quantity,
                'price' => $recipe->pivot->price,
                'expire_date' => $recipe->pivot->expire_date,
                'total_price' => $recipe->pivot->total_price,
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
            'created_by' => [
                'id' => $invoice->createdBy?->id,
                'name' => $invoice->createdBy?->name,
            ],
            'code' => $invoice->code,
            'invoice_date' => Carbon::parse($invoice->invoice_date)->format('Y-m-d'),
            'status' => $invoice->status,
            'invoice_price' => $invoice->invoice_price,
            'total_price' => $invoice->total_price,
            'image' => $invoice->image ? (string) config('app.url').$invoice->image : null,
            'type' => $invoice->type,
            'note' => $invoice->note,
            'recipes' => $formatedPivotsRecipes,
            'registration_date' => $invoice->created_at->format('Y-m-d'),
            'updated_at' => $invoice->updated_at->format('Y-m-d'),
        ];
    }
}
