<?php

namespace App\Http\Requests\Visitor;

use App\Http\Requests\ApiBaseRequest;
use Illuminate\Validation\Rule;

class UpdateVisitorRequest extends ApiBaseRequest
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
     */
    public function rules(): array
    {
        $visitorId = $this->route('visitor');

        return [
            'name' => 'sometimes|required|string|max:255',
            'visitor_type' => 'sometimes|required|in:infantry,weapons,civilian',
            'id_type' => 'sometimes|required|string|max:255',
            'id_number' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('visitors', 'id_number')->ignore($visitorId),
            ],
            'nationality' => 'sometimes|required|string|max:255',
            'vehicle_number' => 'nullable|string|max:255',
            'plate_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'signature_path' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الزائر مطلوب',
            'visitor_type.required' => 'نوع الزائر مطلوب',
            'visitor_type.in' => 'نوع الزائر غير صحيح',
            'id_number.required' => 'رقم الهوية مطلوب',
            'id_number.unique' => 'رقم الهوية موجود بالفعل',
            'nationality.required' => 'الجنسية مطلوبة',
        ];
    }
}