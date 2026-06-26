<?php

namespace App\Transformers\Product;

use App\Models\Product;
use App\Transformers\BaseTransformer;

class AbstractProductDepartmentTransformer extends BaseTransformer
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
    public static function transform(Product $product)
    {
        $lastPrice = $product->prices->where('default', 1)->first();
        $costPrice = CalculateProductCostPrice::calculateCostPrice($product);

        return [
            'id' => (string) $product->pivot->id,
            'name' => $product->name,
            'image' => (string) config('app.url').$product->image ? (string) config('app.url').$product->image : '',
            'cost_price' => $costPrice,
            'price' => $product->price,
            'quantity' => $product->pivot->quantity,
            // 'prices'=>$product->prices

        ];
    }
}
