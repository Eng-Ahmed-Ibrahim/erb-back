<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\ApiBaseRequest;

class addProductRequest extends ApiBaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'products' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'products.required' => 'يجب ادخال  المنتج في الاوردر',
        ];
    }
}
