<?php

namespace App\Http\Requests\Invoices;

use App\Http\Requests\ApiBaseRequest;

class StoreTaintedInvoicesRequest extends ApiBaseRequest
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
            'from' => 'required',
            'invoice_date' => 'required|date',
            'image' => request()->has('image') ? 'image' : 'nullable',
            'note' => 'nullable|string',
            'recipes' => 'required|array',
            'recipes.*.recipe_id' => 'required|string|exists:recipes,id',
            'recipes.*.quantity' => 'required|numeric',
            'recipes.*.expire_date' => 'required|date',
            'recipes.*.price' => 'required',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'from.required' => 'المورد مطلوب',
            'invoice_date.required' => 'تاريخ الفاتورة مطلوب',
            'invoice_date.date' => 'تاريخ الفاتورة يجب ان يكون تاريخ',
            'image.image' => 'يجب ان تكون الصورة من نوع صورة',
            'recipes.required' => 'المواد مطلوبة',
            'recipes.array' => 'المواد يجب ان تكون مصفوفة',
            'recipes.*.recipe_id.required' => 'المادة مطلوبة',
            'recipes.*.recipe_id.string' => 'المادة يجب ان تكون نص',
            'recipes.*.recipe_id.exists' => 'المادة غير موجودة',
            'recipes.*.quantity.required' => 'الكمية مطلوبة',
            'recipes.*.quantity.numeric' => 'الكمية يجب ان تكون رقم',
            'recipes.*.expire_date.required' => 'تاريخ الانتهاء مطلوب',
            'recipes.*.expire_date.date' => 'تاريخ الانتهاء يجب ان يكون تاريخ',
        ];
    }
}
