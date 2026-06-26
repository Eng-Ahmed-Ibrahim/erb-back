<?php

namespace App\Http\Requests\Role;

use App\Http\Requests\ApiBaseRequest;

class UpdateRoleRequest extends ApiBaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => 'required|string|max:255|unique:roles,name,'.$this->id.',id',
            'permissions' => 'required|array',
            'permissions.ids.*' => 'string|exists:permissions,id',
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        // arabic translation
        return [
            'role.required' => 'اسم الصلاحية مطلوب',
            'role.string' => 'اسم الصلاحية يجب ان يكون نص',
            'role.max' => 'اسم الصلاحية يجب ان لا يتجاوز 255 حرف',
            'role.unique' => 'اسم الصلاحية موجود مسبقا',
            'permissions.required' => 'الصلاحيات مطلوبة',
            'permissions.array' => 'الصلاحيات يجب ان تكون مصفوفة',
            'permissions.ids.*.string' => 'الصلاحية يجب ان تكون رقم',
            'permissions.ids.*.exists' => 'الصلاحية غير موجودة',
        ];
    }
}
