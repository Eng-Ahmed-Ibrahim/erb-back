<?php

namespace App\Http\Requests\Invoices;

use App\Http\Requests\ApiBaseRequest;

class SearchInvoicesRequest extends ApiBaseRequest
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
            'invoice_price' => 'nullable',
            'code' => 'nullable',
            'from' => 'nullable',
            'to' => 'nullable',
            'supplier_id' => 'nullable',
            'date' => request()->has('date') ? 'array' : 'nullable',
            'date.from' => request()->has('date') ? 'required' : 'nullable',
            'date.to' => request()->has('date') ? 'required:after_or_equal:date.from' : 'nullable',
            'department_id' => 'nullable',
            'status' => 'nullable',
            'created_by' => 'nullable|exists:users,id',
        ];
    }

    public function messages()
    {
        return [
            'from.date' => 'التاريخ يجب ان يكون تاريخ',
            'to.date' => 'التاريخ يجب ان يكون تاريخ',
            'supplier_id.numeric' => 'المورد يجب ان يكون رقم',
            'date.from.required' => 'التاريخ الابتدائي مطلوب',
            'date.to.required' => 'التاريخ النهائي مطلوب',
            'date.to.after_or_equal' => 'التاريخ النهائي يجب ان يكون بعد او يساوي التاريخ الابتدائي',
        ];
    }
}
