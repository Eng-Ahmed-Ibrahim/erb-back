<?php

namespace App\Http\Requests\SubCategory;

use App\Http\Requests\ApiBaseRequest;

class StoreSubCategoryRequest extends ApiBaseRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => request()->has('image') ? 'image|mimes:jpeg,png,jpg,gif' : 'nullable',
            'category_id' => 'required',
        ];
    }

    public function messages()
    {
        // all the messages that you want to return is arbic language
        return [
            'name.required' => 'اسم القسم فرعي مطلوب',
            'name.string' => 'اسم القسم فرعي يجب ان يكون نص',
            'name.max' => 'اسم القسم فرعي يجب ان لا يتجاوز 255 حرف',
            'description.string' => 'الوصف يجب ان يكون نص',
            'image.image' => 'الصورة يجب ان تكون صورة',
            'image.mimes' => 'الصورة يجب ان تكون من الانواع التالية: jpeg,png,jpg,gif',
            'category_id.required' => 'القسم الرئيسي مطلوب',
        ];
    }
}
