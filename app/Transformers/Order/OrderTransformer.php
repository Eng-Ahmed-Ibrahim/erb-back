<?php

namespace App\Transformers\Order;

use App\Models\Order;
use App\Models\OrderPayable;
use App\Transformers\Product\AbstractProductOrderTransformer;
use App\Transformers\Waiter\AbstractWaiterTransformer;
use Carbon\carbon;
use Flugg\Responder\Transformers\Transformer;

class OrderTransformer extends Transformer
{
    protected $relations = ['payable', 'order'];

    protected $load = [];

    public function transform(Order $order, $n = 0)
    {
        $cost_price = $order->products()->get()->sum('cost_price');

        $formatedProducts = [];
        foreach ($order->products()->get() as $product) {
            if ($product->product) {
                $formatedProducts = array_merge($formatedProducts, AbstractProductOrderTransformer::transform($product->product, $order->id) ?? []);
            }
        }

        $totalPayablesPrice = OrderPayable::where('order_id', $order->id)->get()->sum('amount') ?? 0;

        $totalOrderPrice = $totalPayablesPrice ? $totalPayablesPrice : $order->total_price;

        // $orderPrice = auth()->user()->department_id == '3d1e1d26-91ff-40b8-9b2c-139aa79430e9'
        //     ? $order->products()->get()->sum('price')
        //     : 1.12 * $order->products()->get()->sum('price');

        $orderPrice = $order?->department?->has_tax ? 1.12 * $order->products()->get()->sum('price') : $order->products()->get()->sum('price');

        // Calculate secondary currency if client type has preferred currency
        $secondaryCurrency = null;
        if ($order->clientType && $order->clientType->preferred_currency && $order->clientType->currency_divisor) {
            $secondaryCurrency = [
                'code' => $order->clientType->preferred_currency,
                'name_ar' => $order->clientType->preferred_currency_name_ar,
                'divisor' => $order->clientType->currency_divisor,
                'price' => ceil($orderPrice / $order->clientType->currency_divisor),
                'total_price' => ceil($totalOrderPrice / $order->clientType->currency_divisor),
            ];
        }

        return [
            'id' => (string) $order->id,
            'status' => $order->status,
            'code' => $order->code,
            'order_date' => ('وقت الطلب') . ': ' . carbon::parse($order->order_date)->format('Y-m-d  h:i:s')
                . ($order->closed_at ? ' || ' . __('وقت التجهيز') . ' : ' . carbon::parse($order->closed_at)->format('Y-m-d  h:i:s') : ''),
            'date' => $order->order_date,
            'comment' => $order->comment,
            'payables' => $order->payables()->get()->toArray(),
            'deleviery_type' => $order->deleviery_type,
            'table_number' => $order->table_number,
            'payment_method' => $order->paymentMethod?->label,
            'payment_method_id' => $order->paymentMethod?->id,
            'casher' => $order->user?->name,
            'department_id' => $order->department_id,
            'department' => $order->department?->name,
            'client' => $order->client?->name,
            'price' => $orderPrice,
            'tax' => $order->tax,
            'discount' => $order->discount,
            // 'total_price' => number_format($totalOrderPrice,2),
            'total_price' => $totalOrderPrice,
            'client_military_number' => $order->client->military_number ?? '',
            'client_type' => $order->clientType?->name,
            'client_id' => $order->client?->id,
            'client_type_id' => $order->clientType?->id,
            'discount_name' => $order->clientType?->name,
            'products' => $formatedProducts,
            'cost_price' => $cost_price,
            'waiter' => $order->waiter ? AbstractWaiterTransformer::transform($order->waiter) : [],
            'is_printed' => $order->is_printed,
            'secondary_currency' => $secondaryCurrency,
        ];
    }
}
