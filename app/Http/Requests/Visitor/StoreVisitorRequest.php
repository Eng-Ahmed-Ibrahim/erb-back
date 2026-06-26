<?php

namespace App\Http\Requests\Visitor;

use App\Models\Visitor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisitorRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'client_type_id' => ['required', 'string', 'exists:client_types,id'],
            'id_type' => [
                'required',
                'string',
                Rule::in([
                    Visitor::ID_TYPE_NATIONAL_ID,
                    Visitor::ID_TYPE_PASSPORT,
                    Visitor::ID_TYPE_MILITARY_ID,
                ]),
            ],
            'id_number' => [
                'required', 
                'string',
                'max:50',
                Rule::unique('visitors', 'id_number')->ignore($this->visitor),
            ],
            'nationality' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'emergency_contact' => ['nullable', 'string', 'max:20'],
            'vehicle_number' => ['nullable', 'string', 'max:50'],
            'plate_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'signature_path' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client_type_id.exists' => 'The selected client type is invalid.',
            'id_type.in' => 'The ID type must be one of: National ID, Passport, or Military ID.',
            'id_number.unique' => 'This ID number is already registered in the system.',
            'phone.required' => 'A contact phone number is required.',
            'nationality.required' => 'Please specify the nationality.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'visitor name',
            'client_type_id' => 'client type',
            'id_type' => 'ID type',
            'id_number' => 'ID number',
            'nationality' => 'nationality',
            'phone' => 'phone number',
            'emergency_contact' => 'emergency contact',
            'vehicle_number' => 'vehicle number',
            'plate_number' => 'plate number',
            'notes' => 'notes',
            'signature_path' => 'signature',
        ];
    }
}