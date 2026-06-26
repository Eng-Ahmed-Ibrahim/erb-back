<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Incentive extends Model implements Auditable
{
    use HasFactory
    , \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'employee_id',
        'job_id',
        'discount',
        'reward',
        'total_incentives',
        'point_value',
        'points',
        'excellence_bonus',
        'sim_card_deduction',
        'advance',
        'other_deductions',
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
