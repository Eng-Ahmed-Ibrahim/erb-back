<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\ApiBaseRequest;

class AddRecipeToProductRequest extends ApiBaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'product_id' => 'required',
            'recipes' => 'required',
        ];
    }

    public function messages()
    {
        // all the messages that you want to return is arbic language
        return [
            'product_id.required' => 'المنتج مطلوب',
            'recipes.required' => 'يجب ادخال مكون علي الاقل ',
        ];
    }
}
