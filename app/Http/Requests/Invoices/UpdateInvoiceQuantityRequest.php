<?php

namespace App\Http\Requests\Invoices;

use App\Http\Requests\ApiBaseRequest;

class UpdateInvoiceQuantityRequest extends ApiBaseRequest
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
            'recipes' => 'required|array',
            'recipes.*.recipe_id' => 'required|string|exists:recipes,id',
            'recipes.*.price' => 'required|numeric',
            'recipes.*.quantity' => 'required|numeric',
            'recipes.*.expire_date' => 'required|date',
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
            'recipes.required' => 'يجب ادخال المواد',
            'recipes.array' => 'يجب ادخال المواد',
            'recipes.*.recipe_id.required' => 'يجب اختيار المادة',
            'recipes.*.recipe_id.integer' => 'يجب اختيار المادة',
            'recipes.*.recipe_id.exists' => 'المادة غير موجودة',
            'recipes.*.quantity.required' => 'يجب ادخال الكمية',
            'recipes.*.quantity.numeric' => 'يجب ادخال الكمية',
        ];
    }
}
