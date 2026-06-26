<?php

namespace App\Transformers\Building;

use App\Models\Building;
use App\Transformers\BaseTransformer;

class AbstractBuildingTransformer extends BaseTransformer
{
    protected $relations = [];

    protected $load = [];

    public static function transform(Building $building)
    {
        $totalApartments = $building->apartments->count();
        $availableApartments = $building->apartments->where('is_occupied', false)->where('is_active', true)->count();
        $occupiedApartments = $building->apartments->where('is_occupied', true)->count();
        
        return [
            'id' => (string) $building->id,
            'name' => $building->name,
            'address' => $building->address,
            'floors_count' => $building->floors_count,
            'description' => $building->description,
            'color' => $building->color ?? '#1890ff',
            'is_active' => $building->is_active ?? true,
            'apartments_count' => $totalApartments,
            'total_apartments' => $totalApartments,
            'available_apartments' => $availableApartments,
            'occupied_apartments' => $occupiedApartments,
            'occupancy_rate' => $totalApartments > 0
                ? round(($occupiedApartments / $totalApartments) * 100, 2)
                : 0,
            'occupancy_percentage' => $totalApartments > 0
                ? round(($occupiedApartments / $totalApartments) * 100, 2)
                : 0,
            'created_at' => $building->created_at?->toISOString(),
            'updated_at' => $building->updated_at?->toISOString(),
        ];
    }
}