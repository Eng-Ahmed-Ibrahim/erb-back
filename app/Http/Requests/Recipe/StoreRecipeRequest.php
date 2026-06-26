<?php

namespace App\Http\Requests\Recipe;

use App\Http\Requests\ApiBaseRequest;

class StoreRecipeRequest extends ApiBaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    // done
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'image' => request()->has('image') ? 'image|max:8048' : 'nullable',
            'minimum_limt' => 'required',
            'recipe_category_id' => 'required|string|exists:recipe_categories,id',
            'unit_id' => 'required|string|exists:units,id',
            'days_before_expire' => 'required|integer',
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
            'name.required' => 'الاسم مطلوب',
            'name.string' => 'الاسم يجب ان يكون نص',
            'image.image' => 'الصورة يجب ان تكون صورة',
            'image.max' => 'الصورة يجب ان لا تتعدى 8 ميجا',
            'minimum_limt.required' => 'الحد الادنى مطلوب',
            'recipe_category_id.required' => 'التصنيف مطلوب',
            'recipe_category_id.string' => 'التصنيف يجب ان يكون نص',
            'recipe_category_id.exists' => 'التصنيف غير موجود',
            'unit_id.required' => 'الوحدة مطلوبة',
            'unit_id.string' => 'الوحدة يجب ان تكون نص',
            'unit_id.exists' => 'الوحدة غير موجودة',
            'days_before_expire.required' => 'الايام قبل الانتهاء مطلوبة',
            'days_before_expire.integer' => 'الايام قبل الانتهاء يجب ان تكون رقم',
        ];
    }
}
