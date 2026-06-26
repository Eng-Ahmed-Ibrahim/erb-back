<?php

namespace App\Http\Requests\Department;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentRecipePriceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (auth()->user()->department->type != 'master') {
            throw new AccessDeniedHttpException;
        }

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
            'department_id',
            'recipe_id',
            'unit_price',
        ];
    }

    public function messages(): array
    {
        return [
            'department_id.required' => ' القسم مطلوب',
            'recipe_id.required' => 'برجاء ادخال رقم المنتج ',
            'unit_price.required' => 'برجاء ادخال السعر الجديد للمنتج ',
        ];
    }
}
