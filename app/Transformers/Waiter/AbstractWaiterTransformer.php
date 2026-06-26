<?php

namespace App\Transformers\Waiter;

use App\Models\Waiter;
use App\Transformers\BaseTransformer;

class AbstractWaiterTransformer extends BaseTransformer
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
    public static function transform(Waiter $waiter)
    {
        return [
            'id' => (string) $waiter->id,
            'name' => $waiter->name,
            'image' => $waiter->image ? (string) config('app.url').$waiter->image : '',
            'phone' => $waiter->phone,
        ];
    }
}
