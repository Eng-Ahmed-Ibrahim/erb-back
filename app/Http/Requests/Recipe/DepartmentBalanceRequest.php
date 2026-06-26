<?php

namespace App\Http\Requests\Recipe;

use App\Http\Requests\ApiBaseRequest;
use Carbon\Carbon;

class DepartmentBalanceRequest extends ApiBaseRequest
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
        $toRule = request()->to_date ? [
            'date',
            'after:from_date',
            'before_or_equal:'.Carbon::today()->toDateString(),
        ] : '';

        return [
            'department_id' => 'required|exists:departments,id',
            'from_date' => [
                'required',
                'date',
                'before_or_equal:'.Carbon::yesterday()->toDateString(),
            ],
            'to_date' => $toRule,
        ];
    }

    public function messages(): array
    {
        return [
            'department_id.required' => 'رقم القسم مطلوب',
            'department_id.exists' => 'رقم القسم غير موجود',
            'from_date.required' => 'تاريخ البداية مطلوب',
            'from_date.date' => 'تاريخ البداية يجب ان يكون تاريخ',
            'from_date.before_or_equal' => 'تاريخ البداية يجب ان يكون قبل او يساوي اليوم',
            'to_date.date' => 'تاريخ النهاية يجب ان يكون تاريخ',
            'to_date.after' => 'تاريخ النهاية يجب ان يكون بعد تاريخ البداية',
            'to_date.before_or_equal' => 'تاريخ النهاية يجب ان يكون قبل او يساوي اليوم',
        ];
    }
}
