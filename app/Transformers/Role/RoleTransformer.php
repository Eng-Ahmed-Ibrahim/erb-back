<?php

namespace App\Transformers\Role;

use App\Transformers\BaseTransformer;
use App\Transformers\Permission\PermissionTransformer;
use Spatie\Permission\Models\Role;

class RoleTransformer extends BaseTransformer
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = ['permissions'];

    /**
     * List of autoloaded default relations.
     *
     * @var array
     */
    protected $load = [];

    /**
     * Transform the model.
     *
     * @param  \App\Models\User  $user
     * @return array
     */
    public static function transform(Role $role)
    {

        $permissions = [];
        foreach ($role->permissions as $permission) {
            $permissions[] = PermissionTransformer::transform($permission);
        }

        return [
            'id' => (string) $role->id,
            'name' => $role->name,
            'permissions' => $permissions,
        ];
    }
}
