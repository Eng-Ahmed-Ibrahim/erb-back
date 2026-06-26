<?php

namespace App\Transformers\User;

use App\Models\User;
use App\Transformers\BaseTransformer;
use App\Transformers\Department\AbstractDepartmentTransformer;

class AbstractUserTransformer extends BaseTransformer
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
     * @return array
     */
    public static function transform(User $user)
    {

        // Return the transformed user data
        return [
            'id' => (string) $user->id,
            'username' => (string) $user->username,
            'phone' => (string) $user->phone,
            'name' => (string) $user->name,
            'department' => AbstractDepartmentTransformer::transform($user->department),
            'image' => (string) $user->image ? config('app.url').$user->image : '',
            'permissions' => $user->getPermissionsViaRoles(),

        ];
    }
}
