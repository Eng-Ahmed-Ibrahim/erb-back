<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class OrderPayable extends Model implements Auditable
{
    use HasFactory, HasUlids , \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'order_id',
        'amount',
        'note',
        'receipt_number',

    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
