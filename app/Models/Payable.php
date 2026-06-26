<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Payable extends Model implements Auditable
{
    use HasFactory, HasUuids , \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'amount',
        'type',
        'note',
        'image',
        'client_id',
        'invoice_id',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'payable_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
