<?php

namespace App\Transformers\Apartment;

use App\Models\Apartment;
use App\Transformers\BaseTransformer;
use App\Transformers\Building\AbstractBuildingTransformer;

class AbstractApartmentTransformer extends BaseTransformer
{
    protected $relations = [];

    protected $load = [];

    public static function transform(Apartment $apartment)
    {
        $currentBooking = $apartment->currentBooking();

        return [
            'id' => (string) $apartment->id,
            'apartment_number' => $apartment->apartment_number,
            'building_id' => (string) $apartment->building_id,
            'room_type' => $apartment->room_type,
            'floor_number' => $apartment->floor_number,
            'max_occupancy' => $apartment->max_occupancy,
            'daily_rate' => $apartment->daily_rate,
            'prices' => $apartment->prices ? $apartment->prices->map(function ($price) {
                return [
                    'id' => (string) $price->id,
                    'client_type_id' => (string) $price->client_type_id,
                    'daily_rate' => (float) $price->daily_rate,
                    'weekly_rate' => $price->weekly_rate ? (float) $price->weekly_rate : null,
                    'monthly_rate' => $price->monthly_rate ? (float) $price->monthly_rate : null,
                    'notes' => $price->notes,
                    'client_type' => $price->clientType ? [
                        'id' => (string) $price->clientType->id,
                        'name' => $price->clientType->name,
                        'description' => $price->clientType->description,
                    ] : null,
                ];
            })->toArray() : [],
            'amenities' => $apartment->amenities ?? [],
            'description' => $apartment->description,
            'is_occupied' => $apartment->is_occupied ?? false,
            'is_active' => $apartment->is_active ?? true,
            'status' => ($apartment->is_occupied ?? false) ? 'occupied' : 'available',
            'booking' => $apartment->bookings,
            'current_booking' => $currentBooking ? [
                'id' => (string) $currentBooking->id,
                'visitor_id' => (string) $currentBooking->visitor_id,
                'arrival_datetime' => $currentBooking->arrival_datetime?->format('Y-m-d H:i:s'),
                'checkout_datetime' => $currentBooking->checkout_datetime?->format('Y-m-d H:i:s'),
                'duration_days' => $currentBooking->duration_days,
                'total_amount' => (float) $currentBooking->total_amount,
                'payment_method' => $currentBooking->payment_method,
                'status' => $currentBooking->status,
                'visitor' => $currentBooking->visitor ? [
                    'id' => (string) $currentBooking->visitor->id,
                    'name' => $currentBooking->visitor->name,
                    'id_type' => $currentBooking->visitor->id_type,
                    'id_number' => $currentBooking->visitor->id_number,
                    'nationality' => $currentBooking->visitor->nationality,
                    'phone' => $currentBooking->visitor->phone,
                    'client_type' => $currentBooking->visitor->clientType ? [
                        'id' => (string) $currentBooking->visitor->clientType->id,
                        'name' => $currentBooking->visitor->clientType->name,
                    ] : null,
                ] : null,
            ] : null,
            'building' => $apartment->building ? AbstractBuildingTransformer::transform($apartment->building) : null,
            'created_at' => $apartment->created_at?->toISOString(),
            'updated_at' => $apartment->updated_at?->toISOString(),
        ];
    }
}