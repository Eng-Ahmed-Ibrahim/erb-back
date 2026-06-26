<?php

namespace App\Http\Requests\Category;

use App\Http\Requests\ApiBaseRequest;

class UpdateCategoryRequest extends ApiBaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => ((request()->has('image') && request()->image)) ? 'image|mimes:jpeg,png,jpg,gif,svg|max:8048' : 'nullable',
        ];
    }

    public function messages()
    {
        // all the messages that you want to return is arbic language
        return [
            'name.string' => 'اسم القسم يجب ان يكون نص',
            'name.max' => 'اسم القسم يجب ان لا يتجاوز 255 حرف',
            'description.string' => 'الوصف يجب ان يكون نص',
            'image.image' => 'الصورة يجب ان تكون صورة',
            'image.mimes' => 'الصورة يجب ان تكون من الانواع التالية: jpeg,png,jpg,gif,svg',
            'image.max' => 'الصورة يجب ان لا تتجاوز 8048 كيلوبايت',
        ];
    }
}
