<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\ApiBaseRequest;
use Illuminate\Support\Facades\Auth;

class StoreOrderRequest extends ApiBaseRequest
{
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
        $isQuickOrder = $this->input('is_quick_order', false);

        $rules = [
            'code' => 'required|numeric|unique:orders,code|digits_between:1,7',
            'department_id' => 'required|exists:departments,id',
            'comment' => 'nullable|string',
            'order_date' => 'required',
            'products' => 'required|array',
            'table_number' => 'nullable|sometimes',
            'deleviery_type' => 'nullable',
            'client_id' => 'nullable',
            'military_number' => 'nullable',
            'user_id' => 'required',
            'name' => 'nullable',
            'phone' => 'nullable',
            'waiter_id' => 'nullable',
            'is_quick_order' => 'nullable|boolean',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'status'=>"nullable|in:closed,failed_print,processing,returned,closed"
        ];

        if ($isQuickOrder) {
            $rules['client_type_id'] = 'nullable|exists:client_types,id';
            $rules['payment_method_id'] = 'nullable|exists:payment_methods,id';
        } else {
            $rules['client_type_id'] = 'required|exists:client_types,id';
            $rules['payment_method_id'] = 'nullable|exists:payment_methods,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'code.required' => 'الكود مطلوب',
            'code.numeric' => 'الكود يجب ان يكون رقم',
            'code.unique' => 'الكود موجود مسبقا',
            'table_number.unique' => 'رقم التربيزة موجود مسبقا',
            'code.digits_between' => 'الكود يجب ان يكون بين 1 و 7 ارقام',
            'comment.string' => 'التعليق يجب ان يكون نص',
            'order_date.required' => 'تاريخ الطلب مطلوب',
            'department_id.required' => 'القسم مطلوب',
            'department_id.exists' => 'القسم غير موجود',
            'products.required' => 'المنتجات مطلوبة',
            'products.array' => 'المنتجات يجب ان تكون مصفوفة',
            'table_number.numeric' => 'رقم الطاولة يجب ان يكون رقم',
            'deleviery_type.string' => 'نوع التوصيل يجب ان يكون نص',
            'client_id.exists' => 'العميل غير موجود',
            'client_type_id.required' => 'نوع العميل مطلوب',
            'client_type_id.exists' => 'نوع العميل غير موجود',
            'payment_method_id.exists' => 'طريقه الدفع غير موجود',
            'name.string' => 'الاسم يجب ان يكون نص',
            'is_quick_order.boolean' => 'حقل الطلب السريع يجب ان يكون true او false',
            'discount.numeric' => 'الخصم يجب ان يكون رقم',
            'discount.min' => 'الخصم يجب ان يكون اكبر من او يساوي صفر',
            'tax.numeric' => 'الضريبة يجب ان تكون رقم',
            'tax.min' => 'الضريبة يجب ان تكون اكبر من او تساوي صفر',
        ];
    }

    protected function prepareForValidation()
    {
        $user = Auth::user('api');  // Get the logged-in user
        $departmentId = $user->department->id;  // Assuming a relationship between User and Department

        do {
            $code = mt_rand(1000000, 9999999);
        } while (\App\Models\Order::where('code', $code)->exists());

        $isQuickOrder = $this->input('is_quick_order', false);
        $mergeData = [
            'department_id' => $departmentId,
            'code' => $code,
            'user_id' => $user->id,
        ];

        if ($isQuickOrder) {

            if (!$this->has('client_type_id') || empty($this->input('client_type_id'))) {
                $mergeData['client_type_id'] = '01j49hpdjbqher813xrp68ejz1'; // Default guest type
            }

            if (!$this->has('payment_method_id') || empty($this->input('payment_method_id'))) {
                $mergeData['payment_method_id'] = 'dc2a3eb5-0efd-4bed-a297-8f5b43e8dc13'; // Default cash payment
            }

            if($user->department->clientTypes->count() > 0) {
                $mergeData['client_type_id'] = $user->department->clientTypes->first()->id; 
            }
            
            if (!$this->has('name') || empty($this->input('name'))) {
                $mergeData['name'] = 'Gate Customer';
            }

            if (!$this->has('discount')) {
                $mergeData['discount'] = 0;
            }

            if (!$this->has('tax')) {
                $mergeData['tax'] = 0;
            }

            // Mark as quick order
            $mergeData['is_quick_order'] = true;
        }

        $this->merge($mergeData);
    }
}
