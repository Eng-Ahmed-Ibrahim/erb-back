<?php

namespace App\Transformers\Order;

use App\Models\Order;
use App\Transformers\BaseTransformer;

class AbstractOrderTransformer extends BaseTransformer
{
    protected $relations = [];

    protected $load = [];

    public static function transform(Order $order)
    {

        return [
            'id' => (string) $order->id,
            'status' => $order->status,
            'code' => $order->code,
            'price' => $order->price,
            'tax' => $order->tax,
            'discount' => $order->discount,
            'total_price' => $order->total_price,
            // 'total_price' => $order->getTotalPriceAttribute($client_type->id),
            // 'total_price_after_discount' => $client_type ? $order->getTotalPriceAttribute($client_type->id) - $order->getTotalPriceAttribute($client_type->id) * $client_type->discount / 100 : $order->getTotalPriceAttribute($client_type->id),
            // 'total_price_after_discount_and_tax' => ($client_type ? $order->getTotalPriceAttribute($client_type->id) - $order->getTotalPriceAttribute($client_type->id) * $client_type->discount / 100 : $order->getTotalPriceAttribute($client_type->id)) + ($order->getTotalPriceAttribute($client_type->id) * $client_type->tax / 100 ) ,
            'order_date' => $order->order_date,
            'comment' => $order->comment,
            'department_id' => $order->department_id,
            'table_number' => $order->table_number,
            'discount_name' => $order->clientType?->name,
            'is_printed' => $order->is_printed,
        ];
    }
}
