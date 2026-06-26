<?php

namespace App\Http\Requests\Invoices;

use App\Http\Requests\ApiBaseRequest;

class SearchTaintedInvoiceRequest extends ApiBaseRequest
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
            'date' => request()->has('date') ? 'array' : 'nullable',
            'date.from' => request()->has('date') ? 'required' : 'nullable',
            'date.to' => request()->has('date') ? 'required:after_or_equal:date.from' : 'nullable',
            // 'date' => 'nullable|array',
            // 'date.from' => 'nullable|date',
            // 'date.to' => 'nullable|date|after_or_equal:date.from',
            'status' => 'nullable',
        ];
    }

    public function messages()
    {
        return [
            'from.date' => 'التاريخ يجب ان يكون تاريخ',
            'date.from.required' => 'التاريخ الابتدائي مطلوب',
            'date.to.required' => 'التاريخ النهائي مطلوب',
            'date.to.after_or_equal' => 'التاريخ النهائي يجب ان يكون بعد او يساوي التاريخ الابتدائي',
        ];
    }
}
