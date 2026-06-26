<?php

namespace App\Transformers\Product;

use App\Models\Product;
use App\Transformers\BaseTransformer;
use App\Transformers\Price\PriceTransformer;
use App\Transformers\Recipe\RecipeProductTransformer;

class ProductTransformer extends BaseTransformer
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = ['prices', 'recipes'];

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
        $priceTransformer = new PriceTransformer;
        $RecipeTransformer = new RecipeProductTransformer;
        $parentId = $product->subCategory->id;

        return [(string) config('app.url').
            'id' => (string) $product->id,
            'name' => $product->name,
            'type' => $product->type,
            'status' => $product->status,
            'image' => $product->image ? (string) config('app.url').$product->image : '',
            'is_offer' => (bool) $product->offer,
            'offer' => $product->offer,
            'recipes' => $product->recipes->map(function ($recipe) use ($RecipeTransformer) {
                return $RecipeTransformer->transform($recipe);
            }),
            // 'prices' => $product->prices->map(function ($price) use ($priceTransformer) {
            //     return $priceTransformer->transform($price);
            // }),
            'cost_price' => $product->cost_price,
            'price' => $product->price,
            'description' => $product->description ?? '',
            'category_id' => $product->category->id,
            'sub_category_id' => $parentId,
            'sub_category_name' => $product->subCategory->name,
        ];
    }
}
