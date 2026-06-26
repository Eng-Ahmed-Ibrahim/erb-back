<?php

namespace App\Http\Requests\DiscountReason;

use App\Http\Requests\ApiBaseRequest;

class UpdateDiscountReasonRequest extends ApiBaseRequest
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
            'discount_reason' => 'nullable|string|max:255',
            'discount' => 'nullable',
        ];
    }

    public function messages()
    {
        // all the messages that you want to return is arbic language
        return [
            'discount_reason.string' => 'اسم القسم يجب ان يكون نص',
            'discount_reason.max' => 'اسم القسم يجب ان لا يتجاوز 255 حرف',
        ];
    }
}
