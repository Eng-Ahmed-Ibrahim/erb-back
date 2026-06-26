<?php

namespace App\Transformers\Transaction;

use App\Models\Transaction;
use App\Transformers\BaseTransformer;

class AbstractTransactionTransformer extends BaseTransformer
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
     * @param  \App\Models\Department  $department
     * @return array
     */
    public static function transform(Transaction $transaction)
    {
        return [
            'id' => (string) $transaction->id,
            'resone' => $transaction->resone,
            'amount' => (int) $transaction->amount,
            'type' => $transaction->type,
        ];
    }
}
