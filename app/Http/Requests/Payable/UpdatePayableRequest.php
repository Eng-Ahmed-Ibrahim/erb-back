<?php

namespace App\Http\Requests\Payable;

use App\Http\Requests\ApiBaseRequest;

class UpdatePayableRequest extends ApiBaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $invoiceRule = 'nullable';
        $clientRule = 'nullable';
        if (request()->type == 'invoices') {
            $invoiceRule = 'required|exists:invoices,id';
        } elseif (request()->type == 'salaries' || request()->type == 'incentives') {
            $clientRule = 'required|exists:clients,id';
        }

        return [
            'amount' => 'required|integer',
            'type' => 'required', 'string', 'in:salaries,incentives,miscellaneous,invoices',
            'note' => 'nullable|string',
            'invoice_id' => $invoiceRule,
            'client_id' => $clientRule,
            'image' => request()->image ? 'image' : 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'المبلغ مطلوب',
            'type.required' => 'النوع مطلوب',
            'invoice_id.required' => 'رقم الفاتورة مطلوب',
            'image.image' => 'يجب ان تكون من وع صورة',
            'client_id.required' => 'الموظف مطلوب',
        ];
    }
}
