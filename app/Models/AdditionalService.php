<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdditionalService extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'price',
        'description',
        'is_per_day',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_per_day' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'booking_additional_services')
            ->withPivot(['quantity', 'price', 'notes'])
            ->withTimestamps();
    }
}