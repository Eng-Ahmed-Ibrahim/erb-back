<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class WarehouseSections extends Model implements Auditable
{
    use HasFactory ,  HasUlids , \OwenIt\Auditing\Auditable;

    protected $table = 'warehouse_sections';

    protected $fillable = [
        'name',
    ];
}
