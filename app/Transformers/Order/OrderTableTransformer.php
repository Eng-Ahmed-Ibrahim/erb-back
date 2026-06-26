<?php

namespace App\Transformers\Order;

use App\Models\Order;
use App\Models\OrderPayable;
use App\Transformers\BaseTransformer;
use App\Transformers\Product\AbstractProductOrderTransformer;
use App\Transformers\Waiter\AbstractWaiterTransformer;

class OrderTableTransformer extends BaseTransformer
{
    protected $relations = [];

    protected $load = [];

    public static function transform(Order $order, $table_number = null)
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

        $totalPayablesPrice = OrderPayable::where('order_id', $order->id)->get()->sum('amount') ?? 0;
        $totalOrderPrice = $totalPayablesPrice ? $totalPayablesPrice : $order->total_price;

        // $orderPrice = auth()->user()->department_id == '3d1e1d26-91ff-40b8-9b2c-139aa79430e9'
        //     ? $order->price
        //     : 1.12 * $order->price;

        $orderPrice = $order?->department?->has_tax ? 1.12 * $order->price : $order->price;

        return [
            'id' => (string) $order->id,
            'table_number' => $order->table_number ?? $table_number,
            'is_printed' => $order->is_printed,
            'status' => $order->status,
            'code' => $order->code,
            'order_date' => $order->order_date,
            'comment' => $order->comment,
            'deleviery_type' => $order->deleviery_type,
            'payment_method' => $order->paymentMethod?->label,
            'casher' => $order->user?->name,
            'department_id' => $order->department_id,
            'department' => $order->department?->name,
            'client' => $order->client?->name,
            'price' => $orderPrice,
            'tax' => $order->tax,
            'discount' => $order->discount,
            'total_price' => $totalOrderPrice,
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
