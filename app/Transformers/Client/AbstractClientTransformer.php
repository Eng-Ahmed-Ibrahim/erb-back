<?php

namespace App\Transformers\Client;

use App\Models\Client;
use App\Transformers\BaseTransformer;

class AbstractClientTransformer extends BaseTransformer
{
    protected $relations = [];

    protected $load = [];

    public static function transform(Client $client)
    {
        return [
            'id' => (string) $client->id,
            'name' => $client->name,
            'phone' => $client->phone,
            'military_number' => $client->military_number,
            'is_worker' => $client->is_worker,
            'client_type' => $client->clientType->name,
            'tax' => $client->tax,
            'discount' => $client->discount,
        ];
    }
}
