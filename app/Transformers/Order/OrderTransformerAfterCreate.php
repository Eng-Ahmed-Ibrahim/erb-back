<?php

namespace App\Transformers\Order;

use App\Models\Order;
use App\Transformers\Product\AbstractProductOrderTransformer;
use App\Transformers\Waiter\AbstractWaiterTransformer;
use Flugg\Responder\Transformers\Transformer;

class OrderTransformerAfterCreate extends Transformer
{
    protected $relations = ['payable', 'order'];

    protected $load = [];

    public function transform(Order $order)
    {
        $cost_price = 0;
        if (auth()->user()->department == 'master') {
            $cost_price = $order->products()->get()->sum('cost_price');
        }

        $formatedProducts = [];
        foreach ($order->products()->get() as $product) {
            if ($product->product) {
                $formatedProducts[] = AbstractProductOrderTransformer::transform($product->product, $order->id);
            }
        }

        return
        [
            'id' => (string) $order->id,
            'status' => $order->status,
            'code' => $order->code,
            'order_date' => $order->order_date,
            'comment' => $order->comment,
            'deleviery_type' => $order->deleviery_type,
            'table_number' => $order->table_number,
            'payment_method' => $order->paymentMethod?->label,
            'casher' => $order->user?->name,
            'department_id' => $order->department_id,
            'department' => $order->department?->name,
            'client' => $order->client?->name,
            'price' => $order->products()->get()->sum('price'),
            // 'tax' => $order->tax,
            // 'discount' => $order->discount,
            'total_price' => $order->products()->get()->sum('price'),
            'client_military_number' => $order->client->military_number ?? '',
            'client_type' => $order->clientType?->name,
            'discount_name' => $order->clientType?->name,
            'products' => $formatedProducts,
            'cost_price' => $cost_price,
            'waiter' => $order->waiter ? AbstractWaiterTransformer::transform($order->waiter) : [],
            'is_printed' => $order->is_printed,
        ];
    }
}
