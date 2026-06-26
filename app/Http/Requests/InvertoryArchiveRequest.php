<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvertoryArchiveRequest extends FormRequest
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
            'data.from' => 'required|date',
            'data.to' => 'required|date',
            'data.department_id' => 'required',
            'data.name' => 'nullable',
            'data.category_id' => 'nullable',
            'data.warehouse_section_id' => 'nullable',
        ];
    }

    public function messages()
    {
        // all the messages that you want to return is arbic language
        return [
            'data.from.required' => 'تاريخ البداية مطلوب',
            'data.to.required' => 'تاريخ النهاية مطلوب',
            'data.department_id.required' => 'القسم مطلوب',

        ];
    }
}
