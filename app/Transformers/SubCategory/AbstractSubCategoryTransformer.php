<?php

namespace App\Transformers\SubCategory;

use App\Models\SubCategory;
use App\Transformers\BaseTransformer;

class AbstractSubCategoryTransformer extends BaseTransformer
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
    public static function transform(SubCategory $subCategory)
    {
        return [
            'id' => (string) $subCategory->id,
            'name' => $subCategory->name,
            'description' => $subCategory->description,
            'image' => (string) config('app.url').$subCategory->image ? (string) config('app.url').$subCategory->image : '',
            'category' => [
                'id' => (string) $subCategory->category->id,
                'name' => $subCategory->category->name,
                'description' => $subCategory->category->description,
                'image' => (string) config('app.url').$subCategory->category->image,
            ],
        ];
    }
}
