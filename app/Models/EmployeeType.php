<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use OwenIt\Auditing\Contracts\Auditable;
class EmployeeType extends Model implements Auditable
{
    use HasFactory
    , \OwenIt\Auditing\Auditable;

    protected $table = 'employee_types';

    protected $fillable = [
        'name',
    ];
}
