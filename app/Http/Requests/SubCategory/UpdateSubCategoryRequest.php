<?php

namespace App\Http\Requests\SubCategory;

use App\Http\Requests\ApiBaseRequest;

class UpdateSubCategoryRequest extends ApiBaseRequest
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
            'name' => 'nullable|string',
            'description' => 'nullable|string',
            'category_id' => 'nullable|string|exists:categories,id',
            'image' => ((request()->has('image') && request()->image)) ? 'image|mimes:jpeg,png,jpg,gif,svg|max:8048' : 'nullable',
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
            'name.string' => 'اسم القسم فرعي يجب ان يكون نص',
            'description.string' => 'الوصف يجب ان يكون نص',
            'category_id.string' => 'القسم الرئيسي يجب ان يكون نص',
            'category_id.exists' => 'القسم الرئيسي غير موجود',
            'image.image' => 'الصورة يجب ان تكون صورة',
            'image.mimes' => 'الصورة يجب ان تكون من الانواع التالية: jpeg,png,jpg,gif,svg',
            'image.max' => 'الصورة يجب ان لا تتجاوز 8048 كيلوبايت',
        ];
    }
}
