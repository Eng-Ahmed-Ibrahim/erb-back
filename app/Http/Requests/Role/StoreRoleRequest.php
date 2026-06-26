<?php

namespace App\Http\Requests\Role;

use App\Http\Requests\ApiBaseRequest;

class StoreRoleRequest extends ApiBaseRequest
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
            'role' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'required|array',
            // 'permissions.ids.*' required if permissions is has value and is array
            'permissions.ids.*' => request()->has('permissions') && is_array(request('permissions')) ? 'required|string|exists:permissions,id' : 'nullable',
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.required' => 'الصلاحية مطلوبة',
            'role.string' => 'الصلاحية يجب ان تكون نص',
            'role.max' => 'الصلاحية يجب ان لا تتجاوز 255 حرف',
            'role.unique' => 'الصلاحية موجودة مسبقا',
            'permissions.required' => 'الصلاحيات مطلوبة',
            'permissions.array' => 'الصلاحيات يجب ان تكون مصفوفة',
            'permissions.ids.*.required' => 'الصلاحية مطلوبة',
            'permissions.ids.*.string' => 'الصلاحية يجب ان تكون نص',
            'permissions.ids.*.exists' => 'الصلاحية غير موجودة',
        ];
    }
}
