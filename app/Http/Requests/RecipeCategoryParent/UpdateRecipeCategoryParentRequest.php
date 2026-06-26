<?php

namespace App\Http\Requests\RecipeCategoryParent;

use App\Http\Requests\ApiBaseRequest;

class UpdateRecipeCategoryParentRequest extends ApiBaseRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب',
            'name.string' => 'الاسم يجب ان يكون نص',
            'description.string' => 'الوصف يجب ان يكون نص',
            'image.image' => 'الصورة يجب ان تكون صورة',
            'image.mimes' => 'الصورة يجب ان تكون من الانواع التالية: jpeg,png,jpg,gif,svg',
            'image.max' => 'الصورة يجب ان لا تتجاوز 8048 كيلوبايت',
        ];
    }
}
