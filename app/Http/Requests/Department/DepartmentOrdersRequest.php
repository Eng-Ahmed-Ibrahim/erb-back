<?php

namespace App\Http\Requests\Department;

use App\Http\Requests\ApiBaseRequest;

class DepartmentOrdersRequest extends ApiBaseRequest
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
            'from' => 'nullable',
            'to' => 'nullable',
            'user_id' => 'nullable',
            'waiter_id' => 'nullable',
            'status' => request()->has('status') ? 'required|in:processing,returned,paid,completed,closed' : 'nullable',
            'payment_method_id' => 'nullable',
            'client_id' => 'nullable',
            'client_type_id' => 'nullable',
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
}
