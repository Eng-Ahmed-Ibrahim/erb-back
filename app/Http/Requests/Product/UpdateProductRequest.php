<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\ApiBaseRequest;

class UpdateProductRequest extends ApiBaseRequest
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
            'name' => 'nullable|string|max:255',
            // 'category_id'       => 'nullable',
            'sub_category_id'   => 'nullable',
            'offer' => 'nullable',
            'image' => 'nullable',
            'quantity' => 'nullable',
            'description' => 'nullable',
            'price' => 'required',
            'type' => 'required',
        ];
    }

    public function messages()
    {
        // all the messages that you want to return is arbic language
        return [
            'name.string' => 'اسم المنتج يجب ان يكون نص',
            'name.max' => 'اسم المنتج يجب ان لا يتجاوز 255 حرف',
            // 'image.image' => 'الصورة يجب ان تكون صورة',
            // 'image.mimes' => 'الصورة يجب ان تكون من الانواع التالية: jpeg,png,jpg,gif',
            'description.string' => 'الوصف يجب ان يكون نص',
            'price.required' => 'سعر المنتج مطلوب',
            'type.required' => 'نوع المنتج مطلوب',
        ];
    }
}
