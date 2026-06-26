<?php

namespace App\Http\Requests\Invoices;

use App\Http\Requests\ApiBaseRequest;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class StoreInvoicesRequest extends ApiBaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        // if(auth()->user()->department->type == 'master'){
        //      throw new AccessDeniedHttpException();
        // }
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $ruleTo = 'nullable';
        $ruleFrom = 'nullable';
        $ruleSupplier = 'nullable';
        $ruleCode = 'nullable';
        $ruleInvoiceDate = 'nullable';
        $rulePrice = 'nullable|numeric';
        $ruleExpireDate = 'nullable|date';
        $ruleInvoiceId = 'nullable|string|exists:invoices,id';
        if (request()->type == 'in_coming') {
            $ruleInvoiceDate = 'required|date';
            $ruleCode = 'required|string|unique:invoices,code,NULL,id,type,'.request()->type;
            // $ruleCode           = 'required|string|unique:invoices,code';
            $ruleSupplier = 'required|string|exists:suppliers,id';
            $rulePrice = 'required|numeric';
            $ruleExpireDate = 'required|date';
        } elseif (request()->type == 'out_going') {
            $ruleTo = 'required|string|exists:departments,id';
            $ruleCode = 'required|string|unique:invoices,code,NULL,id,type,'.request()->type;
            $ruleInvoiceId = 'required|string|exists:invoices,id';
        } elseif (request()->type == 'returned') { // returned (مرتجع)
            $ruleCode = 'required|string|unique:invoices,code,NULL,id,type,'.request()->type;
            $ruleFrom = 'required|string|exists:departments,id';
            $ruleExpireDate = 'required|date';
            $rulePrice = 'required|numeric';
            // $ruleInvoiceId = 'required|string|exists:invoices,id';
        } else { // transfare
            $ruleFrom = 'required|string|exists:departments,id';
            $ruleTo = 'required|string|exists:departments,id';
            $ruleInvoiceId = 'required|string|exists:invoices,id';

        }

        return [
            'type' => 'required|in:in_coming,out_going,returned,transfare',
            'from' => $ruleFrom,
            'to' => $ruleTo,
            'supplier_id' => $ruleSupplier,
            'invoice_date' => $ruleInvoiceDate,
            'image' => request()->has('image') ? 'image' : 'nullable',
            'discount' => 'nullable',
            'tax' => 'nullable',
            'note' => 'nullable|string',
            'code' => $ruleCode,
            'recipes' => 'required|array',
            'recipes.*.recipe_id' => 'required|string|exists:recipes,id',
            'recipes.*.quantity' => 'required',
            'recipes.*.price' => $rulePrice,
            'recipes.*.expire_date' => $ruleExpireDate,
            'recipes.*.invoice_id' => $ruleInvoiceId,
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'يجب اختيار نوع الفاتورة',
            'type.in' => 'نوع الفاتورة يجب ان يكون اما داخلية او خارجية او مرتجع',
            'from.required' => 'يجب اختيار القسم المرسل',
            'from.string' => 'يجب اختيار القسم المرسل',
            'from.exists' => 'القسم المرسل غير موجود',
            'to.required' => 'يجب اختيار القسم المستلم',
            'to.string' => 'يجب اختيار القسم المستلم',
            'to.exists' => 'القسم المستلم غير موجود',
            'supplier_id.required' => 'يجب اختيار المورد',
            'supplier_id.string' => 'يجب اختيار المورد',
            'supplier_id.exists' => 'المورد غير موجود',
            'invoice_date.required' => 'يجب ادخال تاريخ الفاتورة',
            'invoice_date.date' => 'يجب ادخال تاريخ صحيح',
            'image.image' => 'يجب ادخال صورة صحيحة',
            'discount.required' => 'يجب ادخال الخصم',
            'tax.required' => 'يجب ادخال الضريبة',
            'note.required' => 'يجب ادخال ملاحظات',
            'code.required' => 'يجب ادخال رقم الفاتورة',
            'code.string' => 'يجب ادخال رقم الفاتورة',
            'code.unique' => 'رقم الفاتورة موجود مسبقا',
            'recipes.required' => 'يجب ادخال المواد',
            'recipes.array' => 'يجب ادخال المواد',
            'recipes.*.recipe_id.required' => 'يجب اختيار المادة',
            'recipes.*.recipe_id.string' => 'يجب اختيار المادة',
            'recipes.*.recipe_id.exists' => 'المادة غير موجودة',
            'recipes.*.quantity.required' => 'يجب ادخال الكمية',
            'recipes.*.quantity.numeric' => 'يجب ادخال الكمية',
            'recipes.*.price.required' => 'يجب ادخال السعر',
            'recipes.*.price.numeric' => 'يجب ادخال السعر',
            'recipes.*.expire_date.required' => 'يجب ادخال تاريخ الانتهاء',
            'recipes.*.expire_date.date' => 'يجب ادخال تاريخ الانتهاء',
            'recipes.*.invoice_id' => 'يجب تحديد فاتورة المورد',
        ];
    }

    public function attributes()
    {
        return [
            'recipes.*.invoice_id' => 'invoice_id',
        ];
    }
}
