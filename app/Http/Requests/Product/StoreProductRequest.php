<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\ApiBaseRequest;

class StoreProductRequest extends ApiBaseRequest
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
            'category_id' => 'required',
            'sub_category_id' => 'required',
            'offer' => 'nullable',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif',
            'price' => 'required',
            // 'prices'            => 'nullable|array',
            'description' => 'nullable',
            'type' => 'required',
        ];
    }

    public function messages()
    {
        // all the messages that you want to return is arbic language
        return [
            'name.required' => 'اسم المنتج مطلوب',
            'name.string' => 'اسم المنتج يجب ان يكون نص',
            'name.max' => 'اسم المنتج يجب ان لا يتجاوز 255 حرف',
            'category_id.required' => 'القسم مطلوب',
            'sub_category_id.required' => 'القسم الفرعي مطلوب',
            'image.image' => 'الصورة يجب ان تكون صورة',
            'image.mimes' => 'الصورة يجب ان تكون من الانواع التالية: jpeg,png,jpg,gif',
            'price.required' => 'السعر مطلوب',
            'description.string' => 'الوصف يجب ان يكون نص',
            'type.required' => 'مكان تنفيذ المنتج مطلوب',
        ];
    }
}
