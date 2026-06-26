<?php

namespace App\Service\transaction;

use App\Models\Transaction;

class TransactionService
{
    public function createTransactionForPayable($payable, $data)
    {
        Transaction::create([
            'resone' => $data['transaction_resone'],
            'amount' => $payable->amount,
            'type' => $data['transaction_type'],
            'status' => $data['transaction_status'],
            'payable_id' => $payable->id,
            'note' => $data['transaction_note'],
            'created_by' => auth('api')->user()->id,
        ]);
    }

    public function createTransactionForOrder($order, $data)
    {

        Transaction::create([
            'resone' => $data['resone'],
            'amount' => $order->amount,
            'type' => 'in',
            'type' => $data['type'],
            'status' => $data['status'],
            'note' => $data['note'],
            'created_by' => auth('api')->user()->id,
        ]);
    }
}
