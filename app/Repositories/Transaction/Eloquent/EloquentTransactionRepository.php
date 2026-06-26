<?php

namespace App\Repositories\Transaction\Eloquent;

use App\Repositories\EloquentBaseRepository;
use App\Repositories\Transaction\TransactionRepository;

class EloquentTransactionRepository extends EloquentBaseRepository implements TransactionRepository
{
    public function adminUpdate($model, $data)
    {

        return $this->update($model, $data);
    }

    public function adminDelete($model)
    {

        return $this->delete($model);
    }

    public function createPayableTransaction($payable)
    {
        $data['amount'] = $payable->amount;
        $data['type'] = 'out';
        $data['status'] = 'done';
        $data['payable_id'] = $payable->id;
        $data['note'] = $payable->type;
        $data['created_by'] = auth('api')->id();

        return $this->create($data);
    }
}
