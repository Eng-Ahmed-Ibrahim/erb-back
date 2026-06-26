<?php

namespace App\Transformers\ClientType;

use App\Models\ClientType;
use App\Transformers\BaseTransformer;
use App\Transformers\PaymentMethod\AbstractPaymentMethodTransformer;

class ClientTypeTransformer extends BaseTransformer
{
    protected $relations = [];

    protected $load = [];

    public static function transform(ClientType $ClientType)
    {
        $clientTypeTransformer = new AbstractPaymentMethodTransformer;

        return [
            'id' => (string) $ClientType->id,
            'name' => $ClientType->name,
            'discount' => $ClientType->discount,
            'monthly_discount_limit' => $ClientType->monthly_discount_limit,
            'tax' => $ClientType->tax,
            'paymentMethods' => $ClientType->paymentMethods->map(function ($method) use ($clientTypeTransformer) {
                return $clientTypeTransformer->transform($method);
            }),
            'new_client' => $ClientType->new_client,
        ];
    }
}
