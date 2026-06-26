<?php

namespace App\Transformers\ClientType;

use App\Models\ClientType;
use App\Transformers\BaseTransformer;

class AbstractClientTypeTransformer extends BaseTransformer
{
    protected $relations = [];

    protected $load = [];

    public static function transform(ClientType $clientType)
    {
        return [
            'id' => (string) $clientType->id,
            'name' => $clientType->name,
            'discount' => $clientType->discount,
            'monthly_discount_limit' => $clientType->monthly_discount_limit,
            'tax' => $clientType->tax,
            'new_client' => $clientType->new_client,
        ];
    }
}
