<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApartmentPrice extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'apartment_id',
        'client_type_id',
        'daily_rate',
        'weekly_rate',
        'monthly_rate',
        'notes',
    ];

    protected $casts = [
        'daily_rate' => 'decimal:2',
        'weekly_rate' => 'decimal:2',
        'monthly_rate' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }

    public function clientType()
    {
        return $this->belongsTo(ClientType::class);
    }

    /**
     * Get the best rate based on duration
     */
    public function getBestRate($durationDays)
    {
        // If staying 30+ days and monthly rate exists, use monthly rate
        if ($durationDays >= 30 && $this->monthly_rate) {
            return $this->monthly_rate * ceil($durationDays / 30);
        }

        // If staying 7+ days and weekly rate exists, use weekly rate
        if ($durationDays >= 7 && $this->weekly_rate) {
            $weeks = floor($durationDays / 7);
            $remainingDays = $durationDays % 7;
            return ($this->weekly_rate * $weeks) + ($this->daily_rate * $remainingDays);
        }

        // Default to daily rate
        return $this->daily_rate * $durationDays;
    }

    /**
     * Get rate per day for display purposes
     */
    public function getDisplayRateAttribute()
    {
        return $this->daily_rate;
    }
}