<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Employee extends Model implements Auditable
{
    use HasFactory
    , \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'id',
        'national_id',
        'name',
        'job_id',
        'department_id',
        'points',
        'employee_type_id',
        'is_active'

    ];

    // Relationships
    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function department()
    {
        return $this->belongsTo(EmployeeDepartments::class);
    }
}
