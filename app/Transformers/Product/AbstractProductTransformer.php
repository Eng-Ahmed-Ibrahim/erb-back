<?php

namespace App\Transformers\Product;

use App\Models\Product;
use App\Transformers\BaseTransformer;
use App\Transformers\Price\PriceTransformer;

class AbstractProductTransformer extends BaseTransformer
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
        $costPrice = CalculateProductCostPrice::calculateCostPrice($product);

        $priceTransformer = new PriceTransformer;

        // $lastPrice = $product->prices->where('default', 1)->first();
        return [
            'id' => (string) $product->id,
            'name' => $product->name,
            'image' => $product->image ? (string) config('app.url').$product->image : '',
            'price' => $product->price,
            'cost_price' => $costPrice,
            'type' => $product->type,
            'sub_category_id' => $product->sub_category_id,
            'status' => $product->status,
            'description' => $product->description,
            'estimated_price' => $costPrice * 1.7,
        ];
    }
}
