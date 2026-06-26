<?php

namespace App\Transformers\Unit;

use App\Models\Unit;
use App\Transformers\BaseTransformer;

class UnitTransformer extends BaseTransformer
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
    public static function transform(Unit $unit)
    {
        return [
            'id' => (string) $unit->id,
            'name' => $unit->name,
        ];
    }
}
