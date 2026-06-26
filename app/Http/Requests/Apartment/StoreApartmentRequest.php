<?php

namespace App\Http\Requests\Apartment;

use App\Http\Requests\ApiBaseRequest;

class StoreApartmentRequest extends ApiBaseRequest
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
        $rules = [
            'building_id' => 'required|exists:buildings,id',
            'apartment_number' => 'required|string|max:255',
            'room_type' => 'required|in:single,double,suite',
            'floor_number' => 'required|integer|min:0|max:50',
            'max_occupancy' => 'required|integer|min:1|max:10',
            'daily_rate' => 'nullable|numeric|min:0', // Made nullable as we'll use dynamic pricing
            'amenities' => 'nullable|array',
            'amenities.*' => 'string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_occupied' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',

            // Dynamic pricing rules
            'prices' => 'required|array|min:1',
            'prices.*.client_type_id' => 'required|exists:client_types,id',
            'prices.*.daily_rate' => 'required|numeric|min:0',
            'prices.*.weekly_rate' => 'nullable|numeric|min:0',
            'prices.*.monthly_rate' => 'nullable|numeric|min:0',
            'prices.*.notes' => 'nullable|string|max:500',
        ];

        // If updating, make the apartment_number unique per building excluding current record
        if ($this->route('id')) {
            $rules['apartment_number'] = 'required|string|max:255|unique:apartments,apartment_number,' . $this->route('id') . ',id,building_id,' . $this->input('building_id');
        } else {
            $rules['apartment_number'] = 'required|string|max:255|unique:apartments,apartment_number,NULL,id,building_id,' . $this->input('building_id');
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'building_id.required' => 'المبنى مطلوب',
            'building_id.exists' => 'المبنى غير موجود',
            'apartment_number.required' => 'رقم الشقة مطلوب',
            'apartment_number.unique' => 'رقم الشقة مُستخدم مسبقاً في هذا المبنى',
            'room_type.required' => 'نوع الغرفة مطلوب',
            'room_type.in' => 'نوع الغرفة غير صحيح',
            'floor_number.required' => 'رقم الطابق مطلوب',
            'floor_number.integer' => 'رقم الطابق يجب أن يكون رقم صحيح',
            'floor_number.min' => 'رقم الطابق يجب أن يكون على الأقل 0',
            'floor_number.max' => 'رقم الطابق يجب أن يكون أقل من أو يساوي 50',
            'max_occupancy.required' => 'الحد الأقصى للسكان مطلوب',
            'max_occupancy.integer' => 'الحد الأقصى للسكان يجب أن يكون رقم صحيح',
            'max_occupancy.min' => 'الحد الأقصى للسكان يجب أن يكون على الأقل 1',
            'max_occupancy.max' => 'الحد الأقصى للسكان يجب أن يكون أقل من أو يساوي 10',
            'daily_rate.numeric' => 'السعر اليومي يجب أن يكون رقم',
            'daily_rate.min' => 'السعر اليومي يجب أن يكون أكبر من أو يساوي 0',
            'amenities.array' => 'المرافق يجب أن تكون مصفوفة',
            'description.max' => 'الوصف يجب أن يكون أقل من 1000 حرف',

            // Dynamic pricing messages
            'prices.required' => 'يجب إضافة أسعار لأنواع العملاء',
            'prices.array' => 'الأسعار يجب أن تكون مصفوفة',
            'prices.min' => 'يجب إضافة سعر واحد على الأقل',
            'prices.*.client_type_id.required' => 'نوع العميل مطلوب',
            'prices.*.client_type_id.exists' => 'نوع العميل غير موجود',
            'prices.*.daily_rate.required' => 'السعر اليومي مطلوب',
            'prices.*.daily_rate.numeric' => 'السعر اليومي يجب أن يكون رقم',
            'prices.*.daily_rate.min' => 'السعر اليومي يجب أن يكون أكبر من أو يساوي 0',
            'prices.*.weekly_rate.numeric' => 'السعر الأسبوعي يجب أن يكون رقم',
            'prices.*.weekly_rate.min' => 'السعر الأسبوعي يجب أن يكون أكبر من أو يساوي 0',
            'prices.*.monthly_rate.numeric' => 'السعر الشهري يجب أن يكون رقم',
            'prices.*.monthly_rate.min' => 'السعر الشهري يجب أن يكون أكبر من أو يساوي 0',
            'prices.*.notes.max' => 'الملاحظات يجب أن تكون أقل من 500 حرف',
        ];
    }
}