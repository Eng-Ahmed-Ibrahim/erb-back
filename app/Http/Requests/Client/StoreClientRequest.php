<?php

namespace App\Http\Requests\Client;

use App\Http\Requests\ApiBaseRequest;

class StoreClientRequest extends ApiBaseRequest
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
            'phone' => 'nullable',
            'military_number' => 'nullable',
            'is_worker' => 'nullable',
            'sallary' => 'nullable',
            'client_type_id' => 'required',
            'tax' => 'nullable',
            'discount' => 'nullable',
        ];
    }

    public function messages()
    {
        // all the messages that you want to return is arbic language
        return [
            'name.required' => 'اسم العميل مطلوب',
            'name.string' => 'اسم العميل يجب ان يكون نص',
            'name.max' => 'اسم العميل يجب ان لا يتجاوز 255 حرف',
        ];
    }
}
