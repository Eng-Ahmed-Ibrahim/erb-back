<?php

namespace App\Http\Requests\Recipe;

use App\Http\Requests\ApiBaseRequest;

class RecipeStatisticsReportRequest extends ApiBaseRequest
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
            'department_id' => 'required',
            'date' => 'array',
            'date.from' => 'required',
            'date.to' => 'required|after_or_equal:date.from',
            'type' => 'nullable',
        ];
    }

    public function messages()
    {
        return [
            'date.from.required' => 'التاريخ الابتدائي مطلوب',
            'date.to.required' => 'التاريخ النهائي مطلوب',
            'date.to.after_or_equal' => 'التاريخ النهائي يجب ان يكون بعد او يساوي التاريخ الابتدائي',
        ];
    }
}
