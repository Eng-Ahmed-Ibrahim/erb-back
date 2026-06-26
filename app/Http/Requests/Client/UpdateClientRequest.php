<?php

namespace App\Http\Requests\Client;

use App\Http\Requests\ApiBaseRequest;

class UpdateClientRequest extends ApiBaseRequest
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
            'phone' => 'nullable',
            'military_number' => 'nullable',
            'is_worker' => 'nullable',
            'sallary' => 'nullable',
            'incentives' => 'nullable',
            'tax' => 'nullable',
            'discount' => 'nullable',
            'client_type_id' => 'nullable',
        ];
    }

    public function messages()
    {
        // all the messages that you want to return is arbic language
        return [
            'name.string' => 'اسم العميل يجب ان يكون نص',
            'name.max' => 'اسم العميل يجب ان لا يتجاوز 255 حرف',
        ];
    }
}
