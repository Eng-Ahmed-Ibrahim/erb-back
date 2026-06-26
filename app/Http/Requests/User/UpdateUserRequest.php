<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiBaseRequest;

class UpdateUserRequest extends ApiBaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'username' => 'required|unique:users,username,'.auth('api')->user()->id,
            'phone' => 'required|numeric|unique:users,phone,'.auth('api')->user()->id,
        ];
    }

    public function messages()
    {
        // all the messages that you want to return is arbic language
        return [
            'name.required' => 'الاسم مطلوب',
            'name.string' => 'الاسم يجب ان يكون نص',
            'name.max' => 'الاسم يجب ان لا يتجاوز 255 حرف',
            'username.required' => 'اسم المستخدم مطلوب',
            'username.unique' => 'اسم المستخدم موجود مسبقا',
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.numeric' => 'رقم الهاتف يجب ان يكون رقم',
            'phone.unique' => 'رقم الهاتف موجود مسبقا',
        ];
    }
}
