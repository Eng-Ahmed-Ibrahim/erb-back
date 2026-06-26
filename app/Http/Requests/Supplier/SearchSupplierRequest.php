<?php

namespace App\Http\Requests\Supplier;

use App\Http\Requests\ApiBaseRequest;

class SearchSupplierRequest extends ApiBaseRequest
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
            'phone' => 'nullable',
            'type' => 'nullable',
            'address' => 'nullable',
            'from_date' => 'nullable',
            'to_date' => 'nullable',
            'warehouse_section_id' => 'nullable',
        ];
    }
}
