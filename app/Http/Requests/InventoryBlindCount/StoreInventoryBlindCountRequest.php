<?php

namespace App\Http\Requests\InventoryBlindCount;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryBlindCountRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'department_id' => 'required|exists:departments,id',
            'waiter_old_id' => 'required|exists:waiters,id|different:waiter_new_id',
            'waiter_new_id' => 'required|exists:waiters,id',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.department_store_id' => 'required|exists:department_store,id',
            'items.*.actual_quantity' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'items.min' => 'يجب اختيار صنف واحد على الأقل لإكمال الجرد.',
        ];
    }
}



