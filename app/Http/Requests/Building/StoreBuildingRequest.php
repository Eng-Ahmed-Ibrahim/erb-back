<?php

namespace App\Http\Requests\Building;

use App\Http\Requests\ApiBaseRequest;

class StoreBuildingRequest extends ApiBaseRequest
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
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'floors_count' => 'required|integer|min:1|max:50',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'nullable|boolean',
        ];

        // If updating, make the id exclude current record
        if ($this->route('id')) {
            $rules['name'] = 'required|string|max:255|unique:buildings,name,' . $this->route('id');
        } else {
            $rules['name'] = 'required|string|max:255|unique:buildings,name';
        }

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'اسم المبنى مطلوب',
            'name.unique' => 'اسم المبنى مُستخدم مسبقاً',
            'name.max' => 'اسم المبنى يجب أن يكون أقل من 255 حرف',
            'address.required' => 'عنوان المبنى مطلوب',
            'address.max' => 'العنوان يجب أن يكون أقل من 500 حرف',
            'floors_count.required' => 'عدد الطوابق مطلوب',
            'floors_count.integer' => 'عدد الطوابق يجب أن يكون رقم صحيح',
            'floors_count.min' => 'عدد الطوابق يجب أن يكون على الأقل 1',
            'floors_count.max' => 'عدد الطوابق يجب أن يكون أقل من أو يساوي 50',
            'description.max' => 'الوصف يجب أن يكون أقل من 1000 حرف',
            'color.regex' => 'لون المبنى يجب أن يكون بصيغة صحيحة (#RRGGBB)',
            'color.max' => 'لون المبنى يجب أن يكون 7 أحرف بحد أقصى',
        ];
    }
} 