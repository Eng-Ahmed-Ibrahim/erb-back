<?php

namespace App\Transformers\PaymentMethod;

use App\Models\PaymentMethod;
use App\Transformers\BaseTransformer;

class PaymentMethodTransformer extends BaseTransformer
{
    protected $relations = [];

    protected $load = [];

    public static function transform(PaymentMethod $PaymentMethod)
    {

        return [
            'id' => (string) $PaymentMethod->id,
            'name' => $PaymentMethod->name,
            // 'label'=>$PaymentMethod->label,
            // 'status' => $PaymentMethod->status,
            // 'type' => $PaymentMethod->type,
            // 'image' => $PaymentMethod->image ? (string) config('app.url') .  $PaymentMethod->image : '',
        ];
    }
}
