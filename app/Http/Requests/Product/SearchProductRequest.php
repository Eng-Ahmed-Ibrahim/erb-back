<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\ApiBaseRequest;

class SearchProductRequest extends ApiBaseRequest
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
            'offer' => 'nullable',
            'prices' => 'nullable',
        ];
    }
}
