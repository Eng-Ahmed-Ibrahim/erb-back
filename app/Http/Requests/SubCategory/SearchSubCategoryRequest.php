<?php

namespace App\Http\Requests\SubCategory;

use App\Http\Requests\ApiBaseRequest;

class SearchSubCategoryRequest extends ApiBaseRequest
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
    public function rules()
    {
        return [
            'name' => 'nullable',
            'description' => 'nullable',
        ];
    }
}
