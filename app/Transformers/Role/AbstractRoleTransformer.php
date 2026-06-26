<?php

namespace App\Transformers\Role;

use App\Transformers\BaseTransformer;
use Spatie\Permission\Models\Role;

class AbstractRoleTransformer extends BaseTransformer
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = [];

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
        return [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions,
        ];
    }
}
