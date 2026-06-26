<?php

namespace App\Http\Requests\Request;

use App\Http\Requests\ApiBaseRequest;

class StoreRequest extends ApiBaseRequest
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
    public function rules()
    {
        return [
            'title' => 'required|string',
            'to_department_id' => 'required|string|exists:departments,id',
            'recipes' => 'required|array',
            'recipes.*.id' => 'required|string|exists:recipes,id',
            'recipes.*.quantity' => 'required|integer',

        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'العنوان مطلوب',
            'title.string' => 'العنوان يجب ان يكون نص',
            'to_department_id.required' => 'القسم المستلم مطلوب',
            'to_department_id.string' => 'القسم المستلم يجب ان يكون نص',
            'to_department_id.exists' => 'القسم المستلم غير موجود',
            'recipes.required' => 'المواد مطلوبة',
            'recipes.array' => 'المواد يجب ان تكون مصفوفة',
            'recipes.*.id.required' => 'المادة مطلوبة',
            'recipes.*.id.string' => 'المادة يجب ان تكون نص',
            'recipes.*.id.exists' => 'المادة غير موجودة',
            'recipes.*.quantity.required' => 'الكمية مطلوبة',
            'recipes.*.quantity.integer' => 'الكمية يجب ان تكون رقم',
        ];
    }
}
