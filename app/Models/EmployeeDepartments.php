<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class EmployeeDepartments extends Model implements Auditable
{
    use HasFactory
    , \OwenIt\Auditing\Auditable;

    protected $table = 'employees_departments';

    protected $fillable = [
        'id',
        'name',
        'points_percentage',
    ];
}
