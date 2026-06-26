<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'id',
        'name',
        'address',
        'floors_count',
        'description',
        'color',
        'is_active',
    ];

    protected $casts = [
        'floors_count' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'is_active' => true,
        'color' => '#1890ff',
    ];

    public function apartments()
    {
        return $this->hasMany(Apartment::class);
    }

    public function availableApartments()
    {
        return $this->apartments()->where('is_occupied', false);
    }

    public function occupiedApartments()
    {
        return $this->apartments()->where('is_occupied', true);
    }

    public function bookings()
    {
        return $this->hasManyThrough(Booking::class, Apartment::class);
    }

    public function activeBookings()
    {
        return $this->bookings()->where('status', 'active');
    }

    /**
     * Get apartments count for the building
     */
    public function getApartmentsCountAttribute()
    {
        return $this->apartments()->count();
    }

    /**
     * Get occupancy statistics for this building
     */
    public function getOccupancyStats()
    {
        $totalApartments = $this->apartments()->count();
        $occupiedApartments = $this->occupiedApartments()->count();
        $availableApartments = $this->availableApartments()->count();

        return [
            'total_apartments' => $totalApartments,
            'occupied_apartments' => $occupiedApartments,
            'available_apartments' => $availableApartments,
            'occupancy_rate' => $totalApartments > 0 ? round(($occupiedApartments / $totalApartments) * 100, 2) : 0,
        ];
    }

    /**
     * Get apartments grouped by room type
     */
    public function getApartmentsByType()
    {
        return $this->apartments->groupBy('room_type')->map(function ($apartments, $type) {
            return [
                'type' => $type,
                'total' => $apartments->count(),
                'occupied' => $apartments->where('is_occupied', true)->count(),
                'available' => $apartments->where('is_occupied', false)->count(),
            ];
        });
    }

    /**
     * Check if building can be deleted
     */
    public function canBeDeleted()
    {
        return $this->apartments()->count() === 0;
    }
} 