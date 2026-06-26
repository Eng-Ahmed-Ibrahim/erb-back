<?php

namespace App\Transformers\Request;

use App\Models\Request;
use App\Transformers\BaseTransformer;

class AbstractRequestTransformer extends BaseTransformer
{
    protected $relations = ['user', 'department', 'recipes'];

    protected $load = [];

    public static function transform(Request $request)
    {

        return [
            'id' => (string) $request->id,
            'title' => $request->title,
            'user' => [
                'id' => $request->user->id,
                'name' => $request->user->name,
            ],
            'from_department' => [
                'id' => $request->fromDepartment->id,
                'name' => $request->fromDepartment->name,
            ],
            'to_department' => [
                'id' => $request->toDepartment->id,
                'name' => $request->toDepartment->name,
            ],
            'status' => $request->status,
            'date' => $request->created_at->format('Y-m-d'),
        ];
    }
}
