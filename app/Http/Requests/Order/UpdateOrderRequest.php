<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\ApiBaseRequest;

class UpdateOrderRequest extends ApiBaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tax' => 'nullable',
            'comment' => 'nullable|string',
            'order_date' => 'nullable',
            'status' => 'nullable',
            'deleviery_type' => 'nullable',

        ];
    }

    public function messages(): array
    {
        return [
            'discount.numeric' => 'الخصم يجب ان يكون رقم',
            'tax.numeric' => 'الضريبة يجب ان تكون رقم',
            'discount_resones.string' => 'سبب الخصم يجب ان يكون نص',
            'comment.string' => 'التعليق يجب ان يكون نص',
            'order_date.required' => 'تاريخ الطلب مطلوب',
            'status.string' => 'الحالة يجب ان تكون نص',
            'table_number.numeric' => 'رقم الطاولة يجب ان يكون رقم',
            'target_department.string' => 'القسم المستهدف يجب ان يكون نص',
            'deleviery_type.string' => 'نوع التوصيل يجب ان يكون نص',
        ];
    }
}
