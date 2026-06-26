<?php

namespace App\Http\Requests\Waiter;

use App\Http\Requests\ApiBaseRequest;

class StoreWaiterRequest extends ApiBaseRequest
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
            'image' => request()->has('image') ? 'image' : 'nullable',
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
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.string' => 'رقم الهاتف يجب ان يكون نص',
        ];
    }
}
