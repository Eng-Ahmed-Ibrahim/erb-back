<?php

namespace App\Http\Requests\Recipe;

use App\Http\Requests\ApiBaseRequest;

class SearchAllRecipeRequest extends ApiBaseRequest
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
            // 'date' => request()->has('date') ? 'array' : 'nullable',
            // 'date.from' => request()->has('date') ? 'required' : 'nullable',
            // 'date.to' => request()->has('date') ? 'required|after_or_equal:date.from' : 'nullable'
            'category_parent_id' => 'nullable',
            'date' => 'nullable|array',
            'date.from' => 'nullable|date',
            'date.to' => 'nullable|date',
        ];
    }

    protected function prepareForValidation()
    {
        $data = $this->all();
        if (isset($data['date']) && $data['date']) {
            $data['date']['from'] = $data['date']['from'] ?? '1970-01-01';
            $data['date']['to'] = $data['date']['to'] ?? now()->addDay()->format('Y-m-d');
        }
        $this->replace($data);
    }

    public function messages(): array
    {
        return [
            'date.from.required' => 'التاريخ الابتدائي مطلوب',
            'date.to.required' => 'التاريخ النهائي مطلوب',
            'date.to.after_or_equal' => 'التاريخ النهائي يجب ان يكون بعد او يساوي التاريخ الابتدائي',
        ];
    }
}
