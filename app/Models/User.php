<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use OwenIt\Auditing\Contracts\Auditable;

class User extends Authenticatable implements Auditable
{
    use HasApiTokens, HasFactory, HasRoles, HasUlids, Notifiable , \OwenIt\Auditing\Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'phone',
        'password',
        'image',
        'department_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function roles()
    {
        return $this
            ->belongsToMany(Role::class, 'model_has_roles', 'model_id', 'role_id')
            ->where('model_type', 'App\Models\User');
    }

    public function isCashier()
    {
        return $this->roles()->first()->id == Role::CASHIER_ROLE_ID || $this->roles()->first()->id ==  Role::ACTIVITIES_CASHIER ;
    }
}
