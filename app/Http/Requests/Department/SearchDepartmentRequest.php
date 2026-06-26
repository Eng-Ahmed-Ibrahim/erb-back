<?php

namespace App\Http\Requests\Department;

use App\Http\Requests\ApiBaseRequest;

class SearchDepartmentRequest extends ApiBaseRequest
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
            'name' => 'nullable',
            'code' => 'nullable',
            'phone' => 'nullable',
            'date' => request()->has('date') ? 'array' : 'nullable',
            'date.from' => request()->has('date') ? 'required' : 'nullable',
            'date.to' => request()->has('date') ? 'required:after_or_equal:date.from' : 'nullable',
            'warehouse_section_id' => 'nullable',
            'section_id' => 'nullable',
            'include_invoices' => 'nullable'
        ];
    }
}
