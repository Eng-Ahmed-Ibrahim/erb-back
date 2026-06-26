<?php

namespace App\Transformers\Order;

use App\Models\Order;
use App\Models\OrderPayable;
use App\Transformers\Product\AbstractProductOrderTransformer;
use App\Transformers\Waiter\AbstractWaiterTransformer;
use Carbon\carbon;
use Flugg\Responder\Transformers\Transformer;

class ShowExtranalOrdersTransformer extends Transformer
{
    protected $relations = ['payable', 'order'];

    protected $load = [];

    public function transform(Order $order, $n = 0)
    {
        $cost_price = 0;
        if (auth()->user()->department == 'master') {
            $cost_price = $order->products()->get()->sum('cost_price');
        }

        $formatedProducts = [];
        foreach ($order->products()->get() as $product) {
            if ($product->product) {
                $formatedProducts = array_merge($formatedProducts, AbstractProductOrderTransformer::transform($product->product, $order->id) ?? []);
            }
        }

        $totalPayablesPrice = OrderPayable::where('order_id', $order->id)->get()->sum('amount') ?? 0;

        return [
            'id' => (string) $order->id,
            'status' => $order->status,
            'code' => $order->code,
            'order_date' => ('وقت الطلب').': '.carbon::parse($order->order_date)->format('Y-m-d  h:i:s')
                .($order->closed_at ? ' || '.__('وقت التجهيز').' : '.carbon::parse($order->closed_at)->format('Y-m-d  h:i:s') : ''),
            'date' => $order->order_date,
            'comment' => $order->comment,
            'payables' => $order->payables()->get()->toArray(),
            'deleviery_type' => $order->deleviery_type,
            'table_number' => $order->table_number,
            'payment_method' => $order->paymentMethod?->label,
            'casher' => $order->user?->name,
            'department_id' => $order->department_id,
            'department' => $order->department?->name,
            'client' => $order->client?->name,
            'price' => $order->products()->get()->sum('price'),
            'tax' => $order->tax,
            'discount' => $order->discount,
            'total_price' => $order->total_price,
            'total_payables' => $totalPayablesPrice,
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
