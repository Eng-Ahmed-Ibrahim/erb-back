<?php

namespace App\Http\Requests\Department;

use App\Http\Requests\ApiBaseRequest;

class StoreDepartmentRequest extends ApiBaseRequest
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
            'code' => 'required|string|unique:departments',
            'phone' => 'required|string',
            'type' => 'required|in:source,reciver,both',
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
            'code.required' => 'الكود مطلوب',
            'code.string' => 'الكود يجب ان يكون نص',
            'code.unique' => 'الكود موجود مسبقا',
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.string' => 'رقم الهاتف يجب ان يكون نص',
            'type.required' => 'نوع القسم مطلوب',
            'type.in' => 'نوع القسم يجب ان يكون source,reciver,both',
        ];
    }
}
