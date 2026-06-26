<?php

namespace App\Http\Requests\Invoices;

use App\Http\Requests\ApiBaseRequest;

class SearchInvoicesDepartmentRequest extends ApiBaseRequest
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
            'date.from' => 'sometimes|date',
            'date.to' => 'sometimes|date|after:from',
        ];
    }

    public function messages()
    {
        return [
            'from.date' => 'التاريخ يجب ان يكون تاريخ',
            'to.date' => 'التاريخ يجب ان يكون تاريخ',
            'to.after' => 'التاريخ النهائي يجب ان يكون بعد التاريخ الابتدائي',
        ];
    }
}
