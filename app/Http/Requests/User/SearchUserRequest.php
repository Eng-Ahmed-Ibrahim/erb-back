<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiBaseRequest;

class SearchUserRequest extends ApiBaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => ['nullable', 'string'],
            'phone' => ['nullable', 'string'],
            'username' => ['nullable', 'string'],
            'role' => ['nullable', 'integer', 'exists:roles,id'],
            'department_id' => 'nullable',
            "user_type"=>'nullable'
        ];
    }
}
