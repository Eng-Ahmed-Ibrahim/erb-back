<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\ApiBaseRequest;

class SubcategoryRequest extends ApiBaseRequest
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
            'name' => 'nullable',
            'offer' => 'nullable',
            'prices' => 'nullable',
            'sub_category_id' => 'nullable',
        ];
    }

    public function messages()
    {
        // all the messages that you want to return is arbic language
        return [
        ];
    }
}
