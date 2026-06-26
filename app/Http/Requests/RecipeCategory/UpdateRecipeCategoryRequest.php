<?php

namespace App\Http\Requests\RecipeCategory;

use App\Http\Requests\ApiBaseRequest;

class UpdateRecipeCategoryRequest extends ApiBaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'description' => 'nullable|string',
            'image' => ((request()->has('image') && request()->image)) ? 'image|mimes:jpeg,png,jpg,gif,svg|max:8048' : 'nullable',
            'category_id' => 'required|exists:recipe_parent_categories,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم التصنيف مطلوب',
            'name.string' => 'اسم التصنيف يجب ان يكون نص',
            'description.string' => 'الوصف يجب ان يكون نص',
            'image.image' => 'الصورة يجب ان تكون صورة',
            'image.mimes' => 'الصورة يجب ان تكون من الانواع التالية: jpeg,png,jpg,gif,svg',
            'image.max' => 'الصورة يجب ان لا تتجاوز 8048 كيلوبايت',
            'category_id.required' => 'التصنيف الرئيسي مطلوب',
            'category_id.exists' => 'التصنيف الرئيسي غير موجود',
        ];
    }
}
