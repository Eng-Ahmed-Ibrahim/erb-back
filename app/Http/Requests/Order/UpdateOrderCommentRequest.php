<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderCommentRequest extends FormRequest
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
            'comment' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required' => 'يجب ادخال حالة الاوردر',
        ];
    }
}
