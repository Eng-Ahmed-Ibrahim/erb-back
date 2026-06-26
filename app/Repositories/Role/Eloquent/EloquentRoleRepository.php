<?php

namespace App\Repositories\Role\Eloquent;

use App\Models\Permission;
use App\Repositories\EloquentBaseRepository;
use App\Repositories\Role\RoleRepository;

class EloquentRoleRepository extends EloquentBaseRepository implements RoleRepository
{
    public function adminShow($RoleId)
    {
        return $this->find($RoleId);
    }

    public function adminCreate($data)
    {
        $data['name'] = $data['role'];
        $data['guard_name'] = 'api';
        $role = $this->create($data);
        $permissions = Permission::whereIn('id', $data['permissions']['ids'])->get();
        foreach ($permissions as $permission) {
            $role->givePermissionTo($permission);
        }

        return $role;
    }

    public function adminUpdate($model, $data)
    {
        $data['name'] = $data['role'];
        $data['guard_name'] = 'api';
        $model->update($data);
        $model->permissions()->detach();
        $permissions = Permission::WhereIn('id', $data['permissions']['ids'])->get();
        $model->syncPermissions($permissions);

        return $model;
    }
}
