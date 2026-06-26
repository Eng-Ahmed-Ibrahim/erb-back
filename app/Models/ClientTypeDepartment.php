<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ClientTypeDepartment extends Model implements Auditable
{
    use HasFactory, HasUlids
    , \OwenIt\Auditing\Auditable;

    protected $table = 'client_type_department';

    protected $fillable = [
        'client_type_id',
        'department_id',
    ];

    public function departments()
    {
        $this->hasMany(Department::class, 'department_id');

    }
}
