<?php

namespace App\Transformers\SubCategory;

use App\Models\SubCategory;
use App\Transformers\BaseTransformer;
use App\Transformers\Product\AbstractProductTransformer;

class SubCategoryTransformer extends BaseTransformer
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = ['products'];

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
    public static function transform(SubCategory $subCategory)
    {
        return [
            'id' => (string) $subCategory->id,
            'name' => $subCategory->name,
            'description' => $subCategory->description,
            'image' => $subCategory->image ? (string) config('app.url').$subCategory->image : '',
            // 'products' => self::formatMany( $subCategory->products, AbstractProductTransformer::class),
            // 'parent' => $subCategory->category->name,
            'category' => [
                'id' => (string) $subCategory->category->id,
                'name' => (string) $subCategory->category->name,
                'description' => (string) $subCategory->category->description,
                'image' => (string) $subCategory->category->image,
            ],
        ];
    }
}
