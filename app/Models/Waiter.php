<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Waiter extends Model implements Auditable
{
    use HasFactory , \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'phone',
        'image',
        'email',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
