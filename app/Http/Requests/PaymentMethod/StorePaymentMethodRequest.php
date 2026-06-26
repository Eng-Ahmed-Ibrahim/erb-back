<?php

namespace App\Http\Requests\PaymentMethod;

use App\Http\Requests\ApiBaseRequest;

class StorePaymentMethodRequest extends ApiBaseRequest
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
            'label' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
            'type' => 'nullable|string|in:cash,postpaid,hospitality,visa',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => ' طريقة الدفع مطلوب',
            'name.string' => 'طريقة الدفع يجب ان يكون نص',
            'name.max' => 'طريقة الدفع يجب ان لا يتجاوز 255 حرف',
            'label.string' => 'اسم طريقه الدفع يجب ان يكون نص',
            'status.in' => 'حاله الدفع يجب ان يكون نص معلوم',
            'type.in' => 'نوع طريقة الدفع يجب ان يكون نص معلوم',
            'image.image' => 'الصورة يجب ان تكون صورة',
            'image.mimes' => 'الصورة يجب ان تكون من الانواع التالية: jpeg,png,jpg,gif',
        ];
    }
}
