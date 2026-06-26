<?php

namespace App\Http\Requests\Waiter;

use App\Http\Requests\ApiBaseRequest;

class UpdateWaiterRequest extends ApiBaseRequest
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
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'image' => ((request()->has('image') && request()->image)) ? 'image|mimes:jpeg,png,jpg,gif,svg|max:8048' : 'nullable',
            'phone' => 'required|string',
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'اسم القسم مطلوب',
            'name.string' => 'اسم القسم يجب ان يكون نص',
            'image.image' => 'الصورة يجب ان تكون صورة',
            'image.mimes' => 'الصورة يجب ان تكون من الانواع التالية: jpeg,png,jpg,gif,svg',
            'image.max' => 'الصورة يجب ان لا تتجاوز 8048 كيلوبايت',
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.string' => 'رقم الهاتف يجب ان يكون نص',

        ];
    }
}
