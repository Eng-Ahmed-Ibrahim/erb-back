<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Job extends Model implements Auditable
{
    use HasFactory , \OwenIt\Auditing\Auditable;

    protected $table = 'employees_jobs';

    protected $fillable = [
        'name',
        'points',
    ];
}
