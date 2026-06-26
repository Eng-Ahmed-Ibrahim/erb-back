<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class DepartmentProduct extends Model implements Auditable
{
    use HasFactory, HasUlids
    , \OwenIt\Auditing\Auditable;

    protected $table = 'department_product';

    protected $fillable = [
        'department_id',
        'product_id',
        'quantity',

    ];
}
