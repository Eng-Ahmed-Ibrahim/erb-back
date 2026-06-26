<?php

namespace App\Transformers\Visitor;

use App\Models\Visitor;
use App\Transformers\BaseTransformer;

class AbstractVisitorTransformer extends BaseTransformer
{
    protected $relations = [];

    protected $load = [];

    public static function transform(Visitor $visitor)
    {
        return [
            'id' => (string) $visitor->id,
            'name' => $visitor->name,
            'visitor_type' => $visitor->visitor_type,
            'client_type_id' => $visitor->client_type_id,
            'color' => $visitor->color,
            'id_type' => $visitor->id_type,
            'phone' => $visitor->phone,
            'id_number' => $visitor->id_number,
            'nationality' => $visitor->nationality,
            'vehicle_number' => $visitor->vehicle_number,
            'plate_number' => $visitor->plate_number,
            'notes' => $visitor->notes,
            'signature_path' => $visitor->signature_path ? config('app.url') . '/storage/' . str_replace('public/', '', $visitor->signature_path) : null,
            'created_at' => $visitor?->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}