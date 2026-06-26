<?php

namespace App\Http\Requests\PaymentMethod;

use App\Http\Requests\ApiBaseRequest;

class UpdatePaymentMethodRequest extends ApiBaseRequest
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
            'name' => 'nullable|string|max:255',
            'label' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
            'type' => 'nullable|string|in:cash,postpaid,hospitality,visa',
            'image' => ((request()->has('image') && request()->image && request()->image != 0)) ? 'image|mimes:jpeg,png,jpg,gif,svg|max:8048' : 'nullable',
        ];
    }

    public function messages()
    {
        return [
            'name.string' => 'اسم القسم يجب ان يكون نص',
            'name.max' => 'اسم القسم يجب ان لا يتجاوز 255 حرف',
            'image.image' => 'الصورة يجب ان تكون صورة',
            'image.mimes' => 'الصورة يجب ان تكون من الانواع التالية: jpeg,png,jpg,gif,svg',
            'image.max' => 'الصورة يجب ان لا تتجاوز 8048 كيلوبايت',
        ];
    }
}
