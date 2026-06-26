<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiBaseRequest;

class StoreUserRequest extends ApiBaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|unique:users,phone',
            'username' => 'required|unique:users,username',
            'password' => 'required',
            'department_id' => 'nullable|exists:departments,id',
            'role' => 'required|string|exists:roles,id',
        ];
    }

    public function messages()
    {
        // arabic messages
        return [
            'name.required' => 'الاسم مطلوب',
            'name.string' => 'الاسم يجب ان يكون نص',
            'name.max' => 'الاسم يجب ان لا يتجاوز 255 حرف',
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.unique' => 'رقم الهاتف مستخدم من قبل',
            'username.required' => 'اسم المستخدم مطلوب',
            'username.unique' => 'اسم المستخدم مستخدم من قبل',
            'password.required' => 'كلمة المرور مطلوبة',
            'department_id.exists' => 'القسم غير موجود',
            'role.required' => 'الصلاحية مطلوبة',
            'role.string' => 'الصلاحية يجب ان تكون نص',
            'role.exists' => 'الصلاحية غير موجودة',
        ];
    }
}
