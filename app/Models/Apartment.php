<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Apartment extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'id',
        'building_id',
        'apartment_number',
        'room_type',
        'floor_number',
        'max_occupancy',
        'daily_rate',
        'amenities',
        'description',
        'is_occupied',
        'is_active',
    ];

    protected $casts = [
        'is_occupied' => 'boolean',
        'is_active' => 'boolean',
        'max_occupancy' => 'integer',
        'floor_number' => 'integer',
        'daily_rate' => 'decimal:2',
        'amenities' => 'array',
    ];

    protected $attributes = [
        'is_occupied' => false,
        'is_active' => true,
        'amenities' => '[]',
    ];

    // Room type constants
    const ROOM_TYPE_SINGLE = 'single';
    const ROOM_TYPE_DOUBLE = 'double';
    const ROOM_TYPE_SUITE = 'suite';

    public static $roomTypes = [
        self::ROOM_TYPE_SINGLE => 'فردي',
        self::ROOM_TYPE_DOUBLE => 'مزدوج',
        self::ROOM_TYPE_SUITE => 'جناح',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class)->with('visitor');
    }

    /**
     * Get current active booking with visitor data
     */
    public function current_booking()
    {
        return $this->hasOne(Booking::class)
            ->with('visitor')
            ->where('checkout_datetime', '>=', now())
            ->where('arrival_datetime', '<=', now())
            ->where('status', Booking::STATUS_ACTIVE)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get pending bookings with visitor data
     */
    public function pending_bookings()
    {
        return $this->hasMany(Booking::class)
            ->with('visitor')
            ->where('status', Booking::STATUS_PENDING)
            ->orderBy('arrival_datetime', 'asc');
    }

    /**
     * Get confirmed bookings with visitor data
     */
    public function confirmed_bookings()
    {
        return $this->hasMany(Booking::class)
            ->with('visitor')
            ->where('status', Booking::STATUS_CONFIRMED)
            ->orderBy('arrival_datetime', 'asc');
    }

    public function prices()
    {
        return $this->hasMany(ApartmentPrice::class);
    }

    public function currentBooking()
    {
        return $this->current_booking()->first();
    }

    public function isAvailable()
    {
        return !$this->is_occupied && $this->is_active;
    }

    /**
     * Check if apartment is available for a specific date range
     */
    public function isAvailableForDateRange($fromDate, $toDate, $currentBookingId = null)
    {
        if (!$this->is_active) {
            return false;
        }
        Log::info("Checking availability for apartment {$this->id} from {$fromDate} to {$toDate}");

        // Check for conflicting bookings (active, confirmed, or pending)
        $conflictingBookings = $this->bookings()
            ->whereIn('status', [Booking::STATUS_ACTIVE, Booking::STATUS_CONFIRMED, Booking::STATUS_PENDING])
            ->when(isset($currentBookingId), function ($query) use ($currentBookingId) {
                $query->where('id', '!=', $currentBookingId);
            })
            ->where(function ($query) use ($fromDate, $toDate, $currentBookingId) {
                $query->where(function ($q) use ($fromDate, $toDate) {
                    $q->whereBetween('arrival_datetime', [$fromDate, $toDate]);
                })->orWhere(function ($q) use ($fromDate, $toDate) {
                    $q->whereBetween('checkout_datetime', [$fromDate, $toDate]);
                })->orWhere(function ($q) use ($fromDate, $toDate) {
                    $q->where('arrival_datetime', '<=', $fromDate)
                        ->where('checkout_datetime', '>=', $toDate);
                });
            })
            ->exists();

        return !$conflictingBookings;
    }

    public function markAsOccupied()
    {
        $this->update(['is_occupied' => true]);
    }

    public function markAsAvailable()
    {
        $this->update(['is_occupied' => false]);
    }

    /**
     * Get the current active booking with visitor data
     */
    public function getBookingAttribute()
    {
        return $this->current_booking()->first();
    }

    /**
     * Get price for specific client type
     */
    public function getPriceForClientType($clientTypeId)
    {
        return $this->prices()->where('client_type_id', $clientTypeId)->first();
    }

    /**
     * Get daily rate for specific client type
     */
    public function getDailyRateForClientType($clientTypeId)
    {
        $price = $this->getPriceForClientType($clientTypeId);
        return $price ? $price->daily_rate : $this->daily_rate; // fallback to old daily_rate if exists
    }

    /**
     * Calculate total price for client type and duration
     */
    public function calculateTotalPrice($clientTypeId, $durationDays)
    {
        $price = $this->getPriceForClientType($clientTypeId);
        if ($price) {
            return $price->getBestRate($durationDays);
        }

        // Fallback to old pricing structure
        return $this->daily_rate * $durationDays;
    }
}