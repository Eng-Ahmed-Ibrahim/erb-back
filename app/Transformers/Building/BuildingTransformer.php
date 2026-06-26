<?php

namespace App\Transformers\Building;

use App\Models\Building;
use App\Transformers\BaseTransformer;
use App\Transformers\Apartment\AbstractApartmentTransformer;

class BuildingTransformer extends BaseTransformer
{
    protected $relations = [];

    protected $load = [];

    public static function transform(Building $building)
    {
        return [
            'id' => (string) $building->id,
            'name' => $building->name,
            'color' => $building->color,
            'created_at' => $building->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $building->updated_at?->format('Y-m-d H:i:s'),

            // Statistics
            'total_apartments' => $building->apartments?->count() ?? 0,
            'available_apartments' => $building->apartments?->where('is_occupied', false)->count() ?? 0,
            'occupied_apartments' => $building->apartments?->where('is_occupied', true)->count() ?? 0,
            'occupancy_percentage' => self::calculateOccupancyPercentage($building),

            // Apartments by type
            'apartments_by_type' => self::getApartmentsByType($building),

            // Apartment details if loaded
            'apartments' => $building->apartments?->map(function ($apartment) {
                return AbstractApartmentTransformer::transform($apartment);
            })->values()->toArray() ?? [],
        ];
    }

    private static function calculateOccupancyPercentage(Building $building)
    {
        if (!$building->apartments || $building->apartments->count() === 0) {
            return 0;
        }

        $occupiedCount = $building->apartments->where('is_occupied', true)->count();
        $totalCount = $building->apartments->count();

        return round(($occupiedCount / $totalCount) * 100, 2);
    }

    private static function getApartmentsByType(Building $building)
    {
        if (!$building->apartments) {
            return [];
        }

        return $building->apartments->groupBy('room_type')->map(function ($apartments, $type) {
            return [
                'type' => $type,
                'total' => $apartments->count(),
                'occupied' => $apartments->where('is_occupied', true)->count(),
                'available' => $apartments->where('is_occupied', false)->count(),
                'occupancy_rate' => $apartments->count() > 0
                    ? round(($apartments->where('is_occupied', true)->count() / $apartments->count()) * 100, 2)
                    : 0,
            ];
        })->values()->toArray();
    }
}