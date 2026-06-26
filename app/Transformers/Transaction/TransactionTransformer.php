<?php

namespace App\Transformers\Transaction;

use App\Models\Transaction;
use Flugg\Responder\Transformers\Transformer;

class TransactionTransformer extends Transformer
{
    protected $relations = ['payable', 'order'];

    protected $load = [];

    public function transform(Transaction $transaction)
    {
        return [
            'id' => (string) $transaction->id,
            'resone' => $transaction->resone,
            'amount' => (int) $transaction->amount,
            'type' => $transaction->type,
            'payable' => [
                'id' => (int) $transaction->payable->id,
                'title' => $transaction->payable->title,
                'note' => $transaction->payable->note,
                'amount' => $transaction->payable->amount,
                'date' => $transaction->payable->payable_date,
            ],
            'order' => [
                'id' => (string) $transaction->order->id,
                'status' => $transaction->order->status,
                'code' => $transaction->order->code,
                'discount' => $transaction->order->amount,
                'discount_reson' => $transaction->order->order_discount_reson,
                'order_date' => $transaction->order->order_order_date,
                'amount' => $transaction->order->order_amount,
                'department' => $transaction->order->department->name,
            ],
        ];
    }
}
