<?php

namespace App\Http\Requests\Payable;

use App\Http\Requests\ApiBaseRequest;

class StorePayableRequest extends ApiBaseRequest
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
        $invoiceRule = 'nullable';
        $clientRule = 'nullable';
        $imageRule = 'nullable';
        if (request()->type == 'invoices') {
            $invoiceRule = 'required|exists:invoices,id';
        } elseif (request()->type == 'salaries' || request()->type == 'incentives') {
            $clientRule = 'required|exists:clients,id';
        } elseif (request()->type == 'expenses') {
            $imageRule = 'required|image';
        }

        return [
            'amount' => 'required|integer',
            'type' => 'required', 'string', 'in:salaries,incentives,miscellaneous,invoices',
            'note' => 'nullable|string',
            'invoice_id' => $invoiceRule,
            'client_id' => $clientRule,
            'image' => $imageRule,
        ];

    }

    public function messages(): array
    {
        return [
            'amount.required' => 'المبلغ مطلوب',
            'type.required' => 'النوع مطلوب',
            'invoice_id.required' => 'رقم الفاتورة مطلوب',
            'image.required' => 'الصورة مطلوبة',
            'image.image' => 'يجب ان تكون من وع صورة',
            'client_id.required' => 'الموظف مطلوب',
        ];
    }
}
