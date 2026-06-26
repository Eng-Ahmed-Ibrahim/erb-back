<?php

namespace App\Http\Requests\Invoices;

use App\Http\Requests\ApiBaseRequest;

class UpdateTaintedInvoicesRequest extends ApiBaseRequest
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
            'from.required' => 'يجب اختيار القسم المرسل',
            'from.string' => 'يجب اختيار القسم المرسل',
            'from.exists' => 'القسم المرسل غير موجود',
            'invoice_date.required' => 'يجب ادخال تاريخ الفاتورة',
            'invoice_date.date' => 'يجب ادخال تاريخ صحيح',
            'image.image' => 'يجب ادخال صورة صحيحة',
            'note.required' => 'يجب ادخال ملاحظات',
            'recipes.required' => 'يجب ادخال المواد',
            'recipes.array' => 'يجب ادخال المواد',
            'recipes.*.recipe_id.required' => 'يجب اختيار المادة',
            'recipes.*.recipe_id.string' => 'يجب اختيار المادة',
            'recipes.*.recipe_id.exists' => 'المادة غير موجودة',
            'recipes.*.quantity.required' => 'يجب ادخال الكمية',
            'recipes.*.quantity.numeric' => 'يجب ادخال الكمية',
            'recipes.*.expire_date.required' => 'يجب ادخال تاريخ الانتهاء',
            'recipes.*.expire_date.date' => 'يجب ادخال تاريخ الانتهاء',
        ];
    }
}
