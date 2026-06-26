<?php

namespace App\Transformers\order;

use App\Models\DeletedOrder;
use App\Models\User;
use App\Transformers\Waiter\AbstractWaiterTransformer;
use Carbon\carbon;
use Flugg\Responder\Transformers\Transformer;

class DeletedOrderTransformer extends Transformer
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
     * @param  \App\Models\DeletedOrder  $deletedOrder
     * @return array
     */
    public function transform(DeletedOrder $order)
    {
        // $formatedProducts = $order->products()->get()->toArray();
        $formatedProducts = $order->products()->get()->map(function ($orderProduct) {
            return [
                'product_id_in_order' => $orderProduct->id,
                'name' => $orderProduct->product?->name ?? 'NA',
                'price' => $orderProduct->product?->price ?? 'NA',
                'quantity' => $orderProduct->quantity,
                'total_price' => $orderProduct->price,
                'is_new' => true,
                'product_type' => $orderProduct->product?->type ?? 'NA',
            ];
        })->toArray();

        // $formatedProducts = [];
        // foreach ($order->products()->get() as $orderProduct) {
        //     if ($orderProduct->product) {
        //         $formatedProducts = array_merge($formatedProducts, [
        //             'product_id_in_order' => $orderProduct->id,
        //             'name' => $orderProduct->product->name,
        //             'price' => $orderProduct->product->price,
        //             'quantity' => $orderProduct->quantity,
        //             'total_price' => $orderProduct->price,
        //             'is_new' => true,
        //             'product_type' => $orderProduct->product->type,
        //         ]);
        //     }
        // }

        return [
            'id' => (string) $order->id,
            'status' => $order->status,
            'code' => $order->code,
            'order_date' => ('وقت الطلب').': '.carbon::parse($order->order_date)->format('Y-m-d  h:i:s')
                .($order->closed_at ? ' || '.__('وقت التجهيز').' : '.carbon::parse($order->closed_at)->format('Y-m-d  h:i:s') : ''),
            'date' => $order->order_date,
            'comment' => $order->comment,
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
            'client_military_number' => $order->client->military_number ?? '',
            'client_type' => $order->clientType?->name,
            'client_type_id' => $order->clientType?->id,
            'discount_name' => $order->clientType?->name,
            'products' => $formatedProducts,
            'waiter' => $order->waiter ? AbstractWaiterTransformer::transform($order->waiter) : [],
            'deleted_by' => User::find($order->deleted_by)->name,
            'deletion_note' => $order->deletion_note,
        ];
    }
}
