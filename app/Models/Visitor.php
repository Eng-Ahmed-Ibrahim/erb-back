<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Visitor extends Model implements Auditable
{
    use HasFactory, HasUlids , \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'id',
        'name',
        'client_type_id',
        'id_type',
        'id_number',
        'nationality',
        'phone',
        'emergency_contact',
        'vehicle_number',
        'plate_number',
        'notes',
        'signature_path',
    ];

    protected $casts = [
        'client_type_id' => 'string',
    ];

    // ID type constants
    const ID_TYPE_NATIONAL_ID = 'national_id';
    const ID_TYPE_PASSPORT = 'passport';
    const ID_TYPE_MILITARY_ID = 'military_id';

    // Visitor type constants
    const VISITOR_TYPE_INFANTRY = 'infantry';
    const VISITOR_TYPE_WEAPONS = 'weapons';
    const VISITOR_TYPE_CIVILIAN = 'civilian';

    // Color assignments for visitor types
    const VISITOR_TYPE_COLORS = [
        self::VISITOR_TYPE_INFANTRY => 'navy',
        self::VISITOR_TYPE_WEAPONS => 'brown',
        self::VISITOR_TYPE_CIVILIAN => 'orange',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function getColorAttribute()
    {
        return self::VISITOR_TYPE_COLORS[$this->client_type_id] ?? 'gray';
    }

    public function getCurrentBooking()
    {
        return $this->bookings()->whereNull('checkout_datetime')->first();
    }

    public function clientType()
    {
        return $this->belongsTo(ClientType::class);
    }
}