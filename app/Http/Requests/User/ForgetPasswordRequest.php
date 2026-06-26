<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiBaseRequest;

class ForgetPasswordRequest extends ApiBaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function messages()
    {
        // all the messages that you want to return is arbic language
        return [
            'password.required' => 'كلمة السر مطلوبة',
            'password.string' => 'كلمة السر يجب ان تكون نص',
            'password.min' => 'كلمة السر يجب ان تكون اكبر من 8 احرف',
            'password.confirmed' => 'كلمة السر غير متطابقة',
        ];
    }
}
