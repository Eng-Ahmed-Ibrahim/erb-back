<?php

namespace App\Http\Requests\RecipeCategory;

use App\Http\Requests\ApiBaseRequest;

class StoreRecipeCategoryRequest extends ApiBaseRequest
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
            'description' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:8048',
            'category_id' => 'required|exists:recipe_parent_categories,id',
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
            'name.required' => 'اسم التصنيف مطلوب',
            'description.required' => 'الوصف مطلوب',
            'image.required' => 'الصورة مطلوبة',
            'image.image' => 'الصورة يجب ان تكون من نوع صورة',
            'image.mimes' => 'الصورة يجب ان تكون من الانواع التالية: jpeg,png,jpg,gif,svg',
            'image.max' => 'الصورة يجب ان لا تتعدى 8048 كيلو بايت',
            'category_id.required' => 'التصنيف الرئيسي مطلوب',
        ];
    }
}
