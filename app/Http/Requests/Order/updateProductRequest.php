<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\ApiBaseRequest;

class updateProductRequest extends ApiBaseRequest
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
            'quantity' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'يجب ادخال كمية المنتج',
        ];
    }
}
