<?php

namespace App\Transformers\User;

use App\Models\Department;
use App\Models\ModelHasModel;
use App\Models\Role;
use App\Models\User;
use App\Transformers\BaseTransformer;
use App\Transformers\Department\AbstractDepartmentTransformer;

class UserTransformer extends BaseTransformer
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
        $department = $user->department;
        // $department = AbstractDepartmentTransformer::transform($department);
        if (isset($department->linked_department)) {
            $department->linked_department_name = Department::find($department->linked_department)?->name;
        }
        $reviewer = null;

        if ($user->roles()->first()->id == Role::STOCK_ROLE_ID) {
            $reviewer = ModelHasModel::join('users', 'users.id', '=', 'model_has_model.target_model_id')
                ->where('source_model_id', $user->id)
                ->where('operation', 'reviewed by')
                ->first();

            if ($reviewer) {
                $reviewer = $reviewer->toArray();
            }
        }

        return [
            'id' => (string) $user->id,
            'name' => (string) $user->name,
            'username' => (string) $user->username,
            'phone' => (string) $user->phone,
            'department' => $department,
            'image' => (string) $user->image ? config('app.url').$user->image : '',
            'permissions' => $user->getPermissionsViaRoles(),
            'roles' => $user->roles()->get(),
            'reviewer' => [
                'name' => isset($reviewer) && ! empty($reviewer) ? $reviewer['name'] : null,
                'id' => isset($reviewer) && ! empty($reviewer) ? $reviewer['id'] : null,
            ],
        ];
    }
}
