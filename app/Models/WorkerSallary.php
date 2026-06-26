<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class WorkerSallary extends Model implements Auditable
{
    use HasFactory, HasUlids , \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'sallay',
        'incentives', // حوافز
        'client_id',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
