<?php

namespace App\Transformers\Price;

use App\Models\Client;
use App\Models\ClientType;
use App\Models\Price;
use Flugg\Responder\Transformers\Transformer;

class PriceTransformer extends Transformer
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
     * @return array
     */
    public function transform(Price $price)
    {
        $client_type = ClientType::find($price->client_type_id);
        $client = Client::find($price->client_id);

        return [
            'id' => (string) $price->id,
            'price' => (float) $price->price,
            'name' => (string) $price->name,
            'client_type_id' => $price->client_type_id,
            'client_type_name' => $client_type?->name,
            'client_id' => $price->client_id,
            'client_name' => $client?->name,
            'service' => (float) $price->service,
            'profit' => (float) $price->profit,
        ];
    }
}
