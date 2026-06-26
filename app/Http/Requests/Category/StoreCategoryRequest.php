<?php

namespace App\Http\Requests\Category;

use App\Http\Requests\ApiBaseRequest;

class StoreCategoryRequest extends ApiBaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->checkCreateCategoryPermission('create category');
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
        ];
    }

    public function messages()
    {
        // all the messages that you want to return is arbic language
        return [
            'name.required' => 'اسم القسم مطلوب',
            'name.string' => 'اسم القسم يجب ان يكون نص',
            'name.max' => 'اسم القسم يجب ان لا يتجاوز 255 حرف',
            'description.string' => 'الوصف يجب ان يكون نص',
            'image.image' => 'الصورة يجب ان تكون صورة',
            'image.mimes' => 'الصورة يجب ان تكون من الانواع التالية: jpeg,png,jpg,gif',
        ];
    }
}
