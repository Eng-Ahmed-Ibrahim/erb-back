<?php

namespace App\Transformers\Permission;

use App\Transformers\BaseTransformer;
use Spatie\Permission\Models\Permission;

class PermissionTransformer extends BaseTransformer
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
    public static function transform(Permission $permission)
    {
        return [
            'id' => (string) $permission->id,
            'name' => (string) $permission->name,
            'display_name' => (string) $permission->display_name,
        ];
    }
}
