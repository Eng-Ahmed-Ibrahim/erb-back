<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Client extends Model implements Auditable
{
    use HasFactory, HasUlids
        , \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'phone',
        'military_number',
        'client_type_id',
        'is_worker',
        'tax',
        'discount',
    ];

    public function clientType()
    {
        return $this->belongsTo(ClientType::class, 'client_type_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'client_id');
    }

    public function sallary()
    {
        return $this->hasOne(WorkerSallary::class, 'client_id');
    }
}
