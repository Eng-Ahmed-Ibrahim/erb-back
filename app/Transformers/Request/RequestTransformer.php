<?php

namespace App\Transformers\Request;

use App\Models\Request;
use App\Transformers\BaseTransformer;

class RequestTransformer extends BaseTransformer
{
    protected $relations = ['user', 'fromDepartment', 'toDepartment'];

    protected $load = [];

    public static function transform(Request $request)
    {

        $recipes = $request->recipes;
        $recipes->load('unit');
        $formatedPivotsRecipes = [];

        foreach ($recipes as $recipe) {
            $formatedPivotsRecipes[] = [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'image' => (string) config('app.url').$recipe->image,
                'quantity' => $recipe->pivot->quantity,
                'unit' => [
                    'id' => $recipe->unit->id,
                    'name' => $recipe->unit->name,
                ],
            ];
        }

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
            'recipes' => $formatedPivotsRecipes,
            'date' => $request->created_at->format('Y-m-d'),
        ];
    }
}
