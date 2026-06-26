<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class IncentivesArchive extends Model implements Auditable
{
    use HasFactory
    , \OwenIt\Auditing\Auditable;

    protected $table = 'archived_incentives';

    protected $fillable = [
        'employee_id',
        'job_id',
        'month',
        'discount',
        'reward',
        'total_incentives',
        'point_value',
        'points',
        'excellence_bonus'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // public function department()
    // {
    //     return $this->belongsTo(EmployeeDepartments::class);
    // }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}
