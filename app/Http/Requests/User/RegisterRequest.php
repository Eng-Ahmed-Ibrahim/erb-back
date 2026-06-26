<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiBaseRequest;

class RegisterRequest extends ApiBaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'username' => 'required|unique:users,username',
            'password' => 'required|string|min:6',
            // 'role' => 'required|string|in:suber_admin,admin,accounter,stock_keeper,department_manger',
            'image' => 'nullable',
            'department_id' => 'required',

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
            'password.required' => 'كلمة المرور مطلوبة',
            'password.string' => 'كلمة المرور يجب ان تكون نص',
            'password.min' => 'كلمة المرور يجب ان لا تقل عن 6 حروف',
            // 'role.required' => 'الصلاحية مطلوبة',
            // 'role.string' => 'الصلاحية يجب ان تكون نص',
            // 'role.in' => 'الصلاحية يجب ان تكون احد القيم التالية: suber_admin,admin,accounter,stock_keeper,department_manger',
            'image.image' => 'الصورة يجب ان تكون صورة',
            'department_id.required' => 'القسم مطلوب',
        ];
    }

    /**
     * add image and password to the request
     *
     * @param  $validator
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'image' => config('defaults.user_image_path'),
            'password' => bcrypt($this->password),
        ]);
    }
}
