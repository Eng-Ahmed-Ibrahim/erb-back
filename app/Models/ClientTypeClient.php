<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ClientTypeClient extends Model implements Auditable
{
    use HasFactory
    , \OwenIt\Auditing\Auditable;

    protected $table = 'client_type_client';

    protected $fillable = [
        'client_type_id',
        'client_id',
    ];
}
