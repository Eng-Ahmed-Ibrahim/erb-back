<?php

namespace App\Repositories\User\Eloquent;

use App\Repositories\EloquentBaseRepository;
use App\Repositories\Role\RoleRepository;
use App\Repositories\User\UserRepository;
use Illuminate\Support\Facades\Auth;

class EloquentUserRepository extends EloquentBaseRepository implements UserRepository
{
    private $roleRepository;

    public function __construct()
    {
        parent::__construct(new \App\Models\User);
        $this->roleRepository = app(RoleRepository::class);
    }

    public function adminCreate($data)
    {
        $data['password'] = bcrypt($data['password']);
        $user = $this->create($data);
        $role = $this->roleRepository->find($data['role']);
        $user->assignRole($role);

        return $user;
    }

    public function adminShow($userId)
    {
        return $this->find($userId);
    }

    public function updateProfile($data)
    {
        $user = Auth::user('api');
        $this->update($user, $data);

        return $user;
    }

    public function updatePassword($data)
    {
        $user = Auth::user('api');
        $this->update($user, ['password' => bcrypt($data['password'])]);

        return $user;
    }
}
