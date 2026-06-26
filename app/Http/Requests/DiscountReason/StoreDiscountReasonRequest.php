<?php

namespace App\Http\Requests\DiscountReason;

use App\Http\Requests\ApiBaseRequest;

class StoreDiscountReasonRequest extends ApiBaseRequest
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
            'discount_reason' => 'required|string|max:255',
            'discount' => 'required',
        ];
    }

    public function messages()
    {
        // all the messages that you want to return is arbic language
        return [
            'discount_reason.required' => ' سبب الخصم مطلوب',
            'discount_reason.string' => 'سبب الخصم يجب ان يكون نص',
            'discount_reason.max' => 'سبب الخصم يجب ان لا يتجاوز 255 حرف',
            'discount.required' => ' الخصم مطلوب',
        ];
    }
}
