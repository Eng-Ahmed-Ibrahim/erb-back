<?php

namespace App\Transformers\Category;

use App\Models\Category;
use App\Transformers\BaseTransformer;

class CategoryTransformer extends BaseTransformer
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = ['subCategories'];

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
    public static function transform(Category $category)
    {

        return [
            'id' => (string) $category->id,
            'name' => $category->name,
            'description' => $category->description,
            'image' => (string) config('app.url').$category->image,
            'created_at' => $category->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $category->updated_at->format('Y-m-d H:i:s'),
            'sub_categories' => self::formatMany($category->subCategories, 'App\Transformers\SubCategory\AbstractSubCategoryTransformer'),
        ];
    }
}
