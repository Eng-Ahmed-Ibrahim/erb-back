<?php

namespace App\Http\Requests\Recipe;

use App\Http\Requests\ApiBaseRequest;

class AddToDepartmentRequest extends ApiBaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'department_id' => 'required',
            'recipes' => 'required',
        ];
    }

    public function messages()
    {
        // all the messages that you want to return is arbic language
        return [
            'department_id.required' => 'القسم مطلوب',
            'recipes.required' => 'يجب ادخال منتج علي الاقل ',
        ];
    }
}
