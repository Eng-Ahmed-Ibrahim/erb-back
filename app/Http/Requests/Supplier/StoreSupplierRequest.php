<?php

namespace App\Http\Requests\Supplier;

use App\Http\Requests\ApiBaseRequest;

class StoreSupplierRequest extends ApiBaseRequest
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
            'phone' => 'required|string',
            'type' => 'required|string|in:contracted,local',
            'address' => 'nullable|string',
        ];
    }

    /**
     * Get the validation messages by arabic that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'اسم المورد مطلوب',
            'name.string' => 'اسم المورد يجب ان يكون نص',
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.string' => 'رقم الهاتف يجب ان يكون نص',
            'type.string' => 'نوع المورد يجب ان يكون نص',
            'type.in' => 'نوع المورد يجب ان يكون contracted,local',
            'address.string' => 'العنوان يجب ان يكون نص',
        ];
    }
}
