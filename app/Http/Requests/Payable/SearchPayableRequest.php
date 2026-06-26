<?php

namespace App\Http\Requests\Payable;

use App\Http\Requests\ApiBaseRequest;

class SearchPayableRequest extends ApiBaseRequest
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
            'type' => 'nullable',
            'date' => request()->has('date') ? 'array' : 'nullable',
            'date.from' => request()->has('date') ? 'required' : 'nullable',
            'date.to' => request()->has('date') ? 'required:after_or_equal:date.from' : 'nullable',
        ];
    }

    // protected function prepareForValidation()
    // {
    //     $data = $this->all();
    //     $data['date']['from'] = $data['date']['from'] ?? '1970-01-01';
    //     $data['date']['to'] = $data['date']['to'] ?? now()->addDay()->format('Y-m-d');
    //     $this->replace($data);
    // }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'يجب ادخال نوع الفاتورة',
            'type.string' => 'يجب ادخال نوع الفاتورة',
            'type.in' => 'نوع الفاتورة غير صحيح',
            'date.required' => 'يجب ادخال تاريخ الفاتورة',
            'date.array' => 'يجب ادخال تاريخ الفاتورة',
            'date.from.required' => 'يجب ادخال تاريخ البداية',
            'date.to.required' => 'يجب ادخال تاريخ النهاية',
        ];
    }
}
