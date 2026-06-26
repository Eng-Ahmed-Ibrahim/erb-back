<?php

namespace App\Http\Requests\Invoices;

use App\Http\Requests\ApiBaseRequest;

class UpdateInvoicePriceRequest extends ApiBaseRequest
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
            'discount' => 'nullable',
            'tax' => 'nullable',
            'note' => 'nullable',
            'recipes' => 'required|array',
            'recipes.*.recipe_id' => 'required|string|exists:recipes,id',
            'recipes.*.price' => 'required|numeric',
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
            'discount.required' => 'يجب ادخال الخصم',
            'discount.numeric' => 'يجب ادخال الخصم',
            'tax.required' => 'يجب ادخال الضريبة',
            'tax.numeric' => 'يجب ادخال الضريبة',
            'note.required' => 'يجب ادخال ملاحظات',
            'recipes.required' => 'يجب ادخال المواد',
            'recipes.array' => 'يجب ادخال المواد',
            'recipes.*.recipe_id.required' => 'يجب اختيار المادة',
            'recipes.*.recipe_id.string' => 'يجب ان يكون رقم المادة نص',
            'recipes.*.recipe_id.exists' => 'المادة غير موجودة',
            'recipes.*.price.required' => 'يجب ادخال السعر',
            'recipes.*.price.numeric' => 'يجب ادخال السعر',
        ];
    }
}
