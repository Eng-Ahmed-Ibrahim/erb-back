<?php

namespace App\Http\Requests\Invoices;

use App\Http\Requests\ApiBaseRequest;

class SearchPeriodRequest extends ApiBaseRequest
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
            'from' => 'required|date',
            'to' => 'required|date|after:from',
        ];
    }

    public function messages()
    {
        return [
            'from.required' => 'يجب ادخال تاريخ البداية',
            'to.required' => 'يجب ادخال تاريخ النهاية',
            'from.date' => 'يجب ادخال تاريخ صحيح',
            'to.date' => 'يجب ادخال تاريخ صحيح',
            'to.after' => 'تاريخ النهاية يجب ان يكون بعد تاريخ البداية',
        ];
    }
}
