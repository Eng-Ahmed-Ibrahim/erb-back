<?php

namespace App\Transformers\Invoices;

use App\Models\Invoice;
use App\Transformers\BaseTransformer;
use Carbon\Carbon;

class InCommingInvoiceTransformer extends BaseTransformer
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = ['supplier'];

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

        return [
            'id' => (string) $invoice->id,
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
            'discount' => $invoice->discount,
            'tax' => $invoice->tax,
            'note' => $invoice->note,
            'registration_date' => $invoice->created_at->format('Y-m-d'),
        ];
    }
}
