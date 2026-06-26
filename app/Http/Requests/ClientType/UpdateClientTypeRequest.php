<?php

namespace App\Http\Requests\ClientType;

use App\Http\Requests\ApiBaseRequest;

class UpdateClientTypeRequest extends ApiBaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'tax' => 'required',
            'discount' => 'required',
            'monthly_discount_limit' => 'nullable|numeric|min:0',
            'new_client' => 'nullable',
            'payment_methods' => 'nullable',

        ];
    }

    public function messages()
    {
        // all the messages that you want to return is arbic language
        return [
            'name.string' => 'نوع العميل يجب ان يكون نص',
            'name.max' => 'نوع العميل يجب ان لا يتجاوز 255 حرف',
        ];
    }
}
