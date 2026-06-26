<?php

namespace App\Http\Requests\Transaction;

use App\Http\Requests\ApiBaseRequest;

class StoreTransactionRequest extends ApiBaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resone' => 'required|string',
            'amount' => 'required|integer',
            'type' => ['required', 'string', 'in:payables,orders'],
            'payable_id' => 'required|string|exists:payables,id',
            'order_id' => 'required|string|exists:orders,id',
        ];
    }

    public function messages(): array
    {
        return [
            'resone.required' => 'السبب مطلوب',
            'resone.string' => 'السبب يجب ان يكون نص',
            'amount.required' => 'المبلغ مطلوب',
            'amount.integer' => 'المبلغ يجب ان يكون رقم',
            'type.required' => 'النوع مطلوب',
            'type.string' => 'النوع يجب ان يكون نص',
            'type.in' => 'النوع يجب ان يكون payables او orders',
            'payable_id.required' => 'المدين مطلوب',
            'payable_id.string' => 'المدين يجب ان يكون نص',
            'payable_id.exists' => 'المدين غير موجود',
            'order_id.required' => 'الطلب مطلوب',
            'order_id.string' => 'الطلب يجب ان يكون نص',
            'order_id.exists' => 'الطلب غير موجود',
        ];
    }
}
