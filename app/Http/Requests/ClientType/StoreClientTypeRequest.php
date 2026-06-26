<?php

namespace App\Http\Requests\ClientType;

use App\Http\Requests\ApiBaseRequest;

class StoreClientTypeRequest extends ApiBaseRequest
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
            'name' => 'required|string|max:255',
            'discount' => 'required',
            'monthly_discount_limit' => 'nullable|numeric|min:0',
            'tax' => 'required',
            'new_client' => 'nullable',
            'payment_methods' => 'required',
        ];
    }

    public function messages()
    {
        // all the messages that you want to return is arbic language
        return [
            'name.required' => 'نوع العميل مطلوب',
            'name.string' => 'نوع العميل يجب ان يكون نص',
            'name.max' => 'نوع العميل يجب ان لا يتجاوز 255 حرف',
        ];
    }
}
