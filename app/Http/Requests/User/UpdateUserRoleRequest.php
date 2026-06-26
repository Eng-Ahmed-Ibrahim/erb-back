<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiBaseRequest;

class UpdateUserRoleRequest extends ApiBaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'role' => 'required|exists:roles,id',
        ];
    }

    public function messages()
    {
        return [
            'role.required' => 'الصلاحية مطلوبة',
            'role.exists' => 'الصلاحية غير موجودة',
            'permissions.required' => 'الصلاحيات مطلوبة',
            'permissions.array' => 'الصلاحيات يجب ان تكون مصفوفة',
            'permissions.ids.*.required' => 'الصلاحية مطلوبة',
            'permissions.ids.*.exists' => 'الصلاحية غير موجودة',
        ];
    }
}
