<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\ApiBaseRequest;

class UpdateOrderStatusRequest extends ApiBaseRequest
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
            'status' => 'required',
            'message' => 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'يجب ادخال حالة الاوردر',
        ];
    }
}
