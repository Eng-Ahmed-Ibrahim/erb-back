<?php

namespace App\Http\Requests\Invoices;

use App\Http\Requests\ApiBaseRequest;

class MoveInvoiceRequest extends ApiBaseRequest
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
            'to' => 'required|exists:departments,id',
            'from' => 'required|exists:departments,id',
            'code' => 'required|exists:invoices,code',
        ];
    }

    public function messages()
    {
        return [
            'to.required' => 'يجب ادخال القسم الي الذي تريد نقل الفاتورة',
            'from.required' => 'يجب ادخال القسم الي الذي تريد نقل الفاتورة',
            'invoice_id.required' => 'يجب ادخال رقم الفاتورة',
            'invoice_id.exists' => 'الفاتورة غير موجودة',
            'to.exists' => 'القسم غير موجود',
            'from.exists' => 'القسم غير موجود',
        ];
    }
}
