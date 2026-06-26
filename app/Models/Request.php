<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'id',
        'title',
        'date',
        'user_id',
        'from_department_id',
        'to_department_id',
        'status',

    ];

    public static function boot()
    {
        parent::boot();

        $user = auth('api')->user();

        if ($user && $user->department && $user->department->type !== 'master') {
            $department = $user->department;

            static::addGlobalScope('requests', function (Builder $builder) use ($department) {
                $builder->where('from_department_id', $department->id)->orWhere('to_department_id', $department->id);
            });
        }
    }

    public function recipes()
    {
        return $this->belongsToMany(Recipe::class)->withPivot('quantity')->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fromDepartment()
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment()
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }
}
