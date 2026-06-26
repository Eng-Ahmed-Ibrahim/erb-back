<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class PaymentMethod extends Model implements Auditable
{
    use HasFactory, HasUlids , \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'label',
        'status',
        'image',
        'type',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function clientTypes()
    {
        return $this->belongsToMany(ClientType::class)
            ->withTimestamps();
    }
}
