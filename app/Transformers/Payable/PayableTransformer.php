<?php

namespace App\Transformers\Payable;

use App\Models\Payable;
use Flugg\Responder\Transformers\Transformer;

class PayableTransformer extends Transformer
{
    protected $relations = ['transactions'];

    protected $load = [];

    public function transform(Payable $payable)
    {

        $transactions = $payable->transactions;
        $formatedPivotstransactions = [];

        foreach ($transactions as $transaction) {
            $formatedPivotstransactions[] = [
                'id' => $transaction->id,
                'resone' => $transaction->resone,
                'amount' => $transaction->amount,
                'type' => $transaction->type,
                'status' => $transaction->status,
                'note' => $transaction->note,
                'created_by' => [
                    'id' => $transaction->user->id,
                    'name' => $transaction->user->name,
                ],
            ];
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
                'image' => $payable->invoice?->image,
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
            'transactions' => $formatedPivotstransactions,
        ];
    }
}
