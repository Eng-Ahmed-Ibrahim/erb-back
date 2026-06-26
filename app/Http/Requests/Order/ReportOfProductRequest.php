<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\ApiBaseRequest;

class ReportOfProductRequest extends ApiBaseRequest
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
            'department_id' => 'required|exists:departments,id',
            'product_id' => 'required|exists:products,id',
            // 'payment_method_id' => 'required|exists:payment_methods,id',
            'from' => 'nullable|date_format:Y-m-d|required_with:to',
            'to' => 'nullable|date_format:Y-m-d|required_with:from',
        ];
    }

    public function messages()
    {
        return [
            'department_id.required' => 'حقل القسم مطلوب',
            'department_id.exists' => 'القسم غير موجود',
            'product_id.required' => 'حقل المنتج مطلوب',
            'product_id.exists' => 'المنتج غير موجود',
            'payment_method_id.required' => 'حقل طريقة الدفع مطلوب',
            'payment_method_id.exists' => 'طريقة الدفع غير موجودة',
            'from.date_format' => 'التاريخ يجب ان يكون بالصيغة Y-m-d',
            'from.required_with' => 'حقل التاريخ من مطلوب',
            'to.date_format' => 'التاريخ يجب ان يكون بالصيغة Y-m-d',
            'to.required_with' => 'حقل التاريخ الى مطلوب',
        ];
    }
}
