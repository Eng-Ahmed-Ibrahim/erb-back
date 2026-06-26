<?php

namespace App\Repositories\Transaction;

use App\Repositories\BaseRepository;

interface TransactionRepository extends BaseRepository
{
    public function adminUpdate($model, $data);

    public function adminDelete($model);

    public function createPayableTransaction($payable);
}
