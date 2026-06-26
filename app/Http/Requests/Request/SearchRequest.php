<?php

namespace App\Http\Requests\Request;

use App\Http\Requests\ApiBaseRequest;

class SearchRequest extends ApiBaseRequest
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
            'title' => 'nullalbe',
            'status' => 'nullable',
            'user_id' => 'nullable',
            'from_department_id' => 'nullable|string|exists:departments,id',
            'to_department_id' => 'nullable|string|different:from_department_id|exists:departments,id',
            'date' => request()->has('date') ? 'array' : 'nullable',
            'date.from' => request()->has('date') ? 'required' : 'nullable',
            'date.to' => request()->has('date') ? 'required' : 'nullable',
        ];
    }

    // protected function prepareForValidation()
    // {
    //     $data = $this->all();
    //     if (isset($data['date']) && $data['date']) {
    //         $data['date']['from'] = $data['date']['from'] ?? '1970-01-01';
    //         $data['date']['to'] = $data['date']['to'] ?? now()->addDay()->format('Y-m-d');
    //     }
    //     $this->replace($data);
    // }

    /**
     * Get the validation messages by arabic that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.nullable' => 'العنوان يجب ان يكون نص',
            'status.nullable' => 'الحالة يجب ان تكون رقم',
            'user_id.nullable' => 'المستخدم يجب ان يكون رقم',
            'from_department_id.nullable' => 'القسم المرسل يجب ان يكون رقم',
            'from_department_id.string' => 'القسم المرسل يجب ان يكون رقم',
            'from_department_id.exists' => 'القسم المرسل غير موجود',
            'to_department_id.nullable' => 'القسم المستلم يجب ان يكون رقم',
            'to_department_id.string' => 'القسم المستلم يجب ان يكون رقم',
            'to_department_id.different' => 'القسم المرسل والمستلم يجب ان يكونوا مختلفين',
            'to_department_id.exists' => 'القسم المستلم غير موجود',
            'date.array' => 'التاريخ يجب ان يكون مصفوفة',
            'date.from.required' => 'التاريخ من مطلوب',
            'date.to.required' => 'التاريخ الى مطلوب',
        ];
    }
}
