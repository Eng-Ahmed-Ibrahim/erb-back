<?php

namespace App\Http\Requests\Unit;

use App\Http\Requests\ApiBaseRequest;

class StoreUnitRequest extends ApiBaseRequest
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

            'name' => 'required|string|max:255',

        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الوحدة مطلوب',
            'name.string' => 'اسم الوحدة يجب ان يكون نص',
            'name.max' => 'اسم الوحدة يجب ان لا يتجاوز 255 حرف',
        ];
    }
}
