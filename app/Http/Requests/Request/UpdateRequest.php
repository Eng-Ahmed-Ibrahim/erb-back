<?php

namespace App\Http\Requests\Request;

use App\Http\Requests\ApiBaseRequest;

class UpdateRequest extends ApiBaseRequest
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
            'recipes.*.id' => 'required|string|exists:recipes,id',
            'recipes.*.quantity' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'recipes.required' => 'الوصفات مطلوبة',
            'recipes.array' => 'الوصفات يجب ان تكون مصفوفة',
            'recipes.*.id.required' => 'الوصفة مطلوبة',
            'recipes.*.id.string' => 'الوصفة يجب ان تكون نص',
            'recipes.*.id.exists' => 'الوصفة غير موجودة',
            'recipes.*.quantity.required' => 'الكمية مطلوبة',
            'recipes.*.quantity.integer' => 'الكمية يجب ان تكون رقم',
        ];
    }
}
