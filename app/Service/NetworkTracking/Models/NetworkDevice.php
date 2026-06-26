<?php

namespace App\Service\NetworkTracking\Models;

use Illuminate\Database\Eloquent\Model;

class NetworkDevice extends Model
{
    protected $fillable = [
        'name',
        'department_id',
        'mac_address', 
        'ip_address', 
        'device_id', 
        'location'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
} 