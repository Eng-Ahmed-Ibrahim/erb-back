<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentActualQuantitiesRequest extends FormRequest
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
            'department_id' => 'required|exists:departments,id',
            'items' => 'required|array',
            'items.*.id' => 'required|exists:department_store,id',
            'items.*.actual_quantity' => 'required|numeric|min:0',
            'discrepancy_note' => 'nullable|string',
            'cashier_id' => 'required|exists:users,id',
            'waiter_id' => 'required|exists:waiters,id',
            'estimated_loss_amount' => 'required|numeric|min:0',
        ];
    }
}
