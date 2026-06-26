<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiBaseRequest;

class LoginRequest extends ApiBaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'username' => 'required',
            'password' => 'required|string|min:6|max:50',
        ];
    }

    public function messages()
    {
        return [
            'username.required' => 'اسم المستخدم مطلوب',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.string' => 'كلمة المرور يجب ان تكون نص',
            'password.min' => 'كلمة المرور يجب ان لا تقل عن 6 حروف',
            'password.max' => 'كلمة المرور يجب ان لا تزيد عن 20 حرف',
        ];
    }
}
