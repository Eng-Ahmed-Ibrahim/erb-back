<?php

namespace App\Transformers\Product;

use App\Models\OrderProduct;
use App\Models\Product;
use App\Transformers\BaseTransformer;

class AbstractProductOrderTransformer extends BaseTransformer
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
    // public static function transform(Product $product,$order_id)
    // {
    //     $orderProduct = OrderProduct::select(['quantity','id','product_type', 'cost_price'])->where(['order_id'=>$order_id,'product_id'=>$product->id])->first();

    //     return [
    //         'product_id_in_order' =>  $orderProduct->id,
    //         'name' => $product->name,
    //         'image' =>    $product->image ? (string) config('app.url') . $product->image : '',
    //         'price' => $product->price,
    //         'quantity' => $orderProduct->quantity,
    //         'product_type'=> $product->type,
    //         'total_price' => $product->price * $orderProduct->quantity,
    //     ];
    // }

    public static function transform(Product $product, $order_id)
    {
        $orderProduct = OrderProduct::select(['quantity', 'id', 'product_type', 'cost_price', 'price', 'recieved_quantity'])
            ->where(['order_id' => $order_id, 'product_id' => $product->id])
            ->first();

        $response = [];

        $productPrice = $orderProduct->quantity ? $orderProduct->price / $orderProduct->quantity : $product->price;
        $recieved_quantity = $orderProduct->recieved_quantity;
        if ($recieved_quantity > 0) {
            $response[] = [
                'product_id_in_order' => $orderProduct->id,
                'name' => $product->name,
                'productId' => $product->id,
                'image' => $product->image ? (string) config('app.url') . $product->image : '',
                'price' => $productPrice,
                'quantity' => $recieved_quantity,
                'product_type' => $product->type,
                'total_price' => $productPrice * $recieved_quantity,
                'is_new' => false,
            ];
        }

        $remainingQuantity = $orderProduct->quantity - $recieved_quantity;
        if ($remainingQuantity > 0) {
            $response[] = [
                'product_id_in_order' => $orderProduct->id,
                'name' => $product->name,
                'productId' => $product->id,
                'image' => $product->image ? (string) config('app.url') . $product->image : '',
                'price' => $productPrice,
                'quantity' => $remainingQuantity,
                'product_type' => $product->type,
                'total_price' => $productPrice * $remainingQuantity,
                'is_new' => true,
            ];
        }

        return $response;
    }
}
