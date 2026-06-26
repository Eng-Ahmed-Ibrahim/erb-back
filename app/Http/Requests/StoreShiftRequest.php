<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Update based on your authorization logic
    }

    public function rules()
    {
        return [
            'department_id' => 'required|string',
            'user_id' => 'required|string',
            'start' => 'required|date',
            'end' => 'required|date|after:start',
        ];
    }
}
