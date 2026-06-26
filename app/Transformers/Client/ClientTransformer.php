<?php

namespace App\Transformers\Client;

use App\Models\Client;
use App\Models\ClientType;
use App\Models\ClientTypeClient;
use App\Transformers\BaseTransformer;
use App\Transformers\Order\AbstractOrderTransformer;

class ClientTransformer extends BaseTransformer
{
    protected $relations = [];

    protected $load = [];

    public static function transform(Client $Client)
    {

        $clientTypes = ClientType::whereIn('id', ClientTypeClient::where('client_id', $Client->id)->pluck('client_type_id')->toArray())->select('id', 'name')->get();

        $orderTransformer = new AbstractOrderTransformer;

        return [
            'id' => (string) $Client->id,
            'name' => $Client->name,
            'phone' => $Client->phone,
            'military_number' => $Client->military_number,
            'sallary' => $Client->sallary?->sallary,
            'incentives' => $Client->sallary?->incentives,
            'client_type_name' => $Client->clientType ? $Client->clientType->name : '',
            'client_type_id' => $Client->clientType ? $Client->clientType->id : '',
            'client_types' => $clientTypes,
            'orders' => $Client->orders->map(function ($p) use ($orderTransformer) {
                return $orderTransformer->transform($p);
            }),
            'is_worker' => $Client->is_worker,
            'tax' => $Client->tax,
            'discount' => $Client->discount,

        ];
    }
}
