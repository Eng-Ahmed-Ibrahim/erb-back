<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\ApiBaseRequest;

class UpdateProductPriceRequest extends ApiBaseRequest
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
            'price' => 'required',
            'service' => 'nullable',
            'profit' => 'nullable',
            'type' => 'nullable',
            'client_type_id' => 'nullable',
            'client_id' => 'nullable',
        ];
    }

    public function messages()
    {
        // all the messages that you want to return is arbic language
        return [
            'name.required' => 'اسم المنتج مطلوب',
            'name.string' => 'اسم المنتج يجب ان يكون نص',
            'name.max' => 'اسم المنتج يجب ان لا يتجاوز 255 حرف',
            // 'price.required' => 'السعر مطلوب',
        ];
    }
}
