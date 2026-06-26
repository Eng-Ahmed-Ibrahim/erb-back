<?php

namespace App\Transformers;

use App\Models\Shift;
use Flugg\Responder\Transformers\Transformer;

class ShiftTransformer extends Transformer
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
     * @param  \App\Models\Shift  $abstractShift
     * @return array
     */
    public function transform(Shift $shift)
    {
        if ($shift == null) {
            return [];
        }

        return [
            'shift_id' => $shift->id,
            'start' => $shift->start,
            'end' => $shift->end,
            'user' => [
                'id' => $shift->user_id,
                'name' => $shift->user->name,
            ],
            'department' => [
                'id' => $shift->department_id,
                'name' => $shift->department->name,
            ],
        ];
    }
}
