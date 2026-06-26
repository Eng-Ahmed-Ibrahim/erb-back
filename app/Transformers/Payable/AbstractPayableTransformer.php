<?php

namespace App\Transformers\Payable;

use App\Models\Payable;
use Flugg\Responder\Transformers\Transformer;

class AbstractPayableTransformer extends Transformer
{
    protected $relations = ['transactions'];

    protected $load = [];

    public function transform(Payable $payable)
    {
        $is_correct = true;
        if ($payable->type == 'invoices') {

            if ($payable?->invoice?->total_price != $payable->amount) {
                $is_correct = false;
            }
        }

        return [
            'id' => (string) $payable->id,
            'amount' => (int) $payable->amount,
            'note' => $payable->note,
            'type' => $payable->type,
            'invoice' => [
                'id' => $payable->invoice?->id,
                'code' => $payable->invoice?->code,
                'invoice_date' => $payable->invoice?->invoice_date,
                'invoice_price' => $payable->invoice?->invoice_price,
                'discount' => $payable->invoice?->discount,
                'tax' => $payable->invoice?->tax,
                'total_price' => $payable->invoice?->total_price,
            ],
            'client' => [
                'id' => $payable->client?->id,
                'name' => $payable->client?->name,
            ],
            'image' => $payable->image ? (string) config('app.url').$payable->image : null,
            'registration_date' => $payable->created_at->format('Y-m-d'),
            'is_correct' => $is_correct,
        ];
    }
}
