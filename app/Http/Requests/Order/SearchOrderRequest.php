<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\ApiBaseRequest;

class SearchOrderRequest extends ApiBaseRequest
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
            'user_id' => 'required',
            'status' => 'nullable',
            'payment_method' => 'nullable',
            'code' => 'nullable',
            'tax' => 'nullable',
            'returned_resones' => 'nullable',
            'comment' => 'nullable',
            'casher' => 'nullable',
            'department_id' => 'nullable|exists:departments,id',
            'date' => request()->has('date') ? 'array' : 'nullable',
            'date.from' => request()->has('date') ? 'required' : 'nullable',
            'date.to' => request()->has('date') ? 'required:after_or_equal:date.from' : 'nullable',
            'show_history' => 'nullable',
            'selected_department' => 'nullable',
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
        ];
    }
}
