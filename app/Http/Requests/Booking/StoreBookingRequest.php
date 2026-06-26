<?php

namespace App\Http\Requests\Booking;

use App\Http\Requests\ApiBaseRequest;

class StoreBookingRequest extends ApiBaseRequest
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
        return [
            // Apartment and basic booking info
            'apartment_id' => 'required|exists:apartments,id',
            'arrival_datetime' => 'required|date',
            'checkout_datetime' => 'required|date|after:arrival_datetime',
            'duration_days' => 'required|integer|min:1|max:365',

            // Payment and amount info
            'total_amount' => 'required|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string|in:cash,card,transfer,postpaid',

            // Optional arrays
            'meals' => 'nullable|array',
            'meals.*' => 'in:breakfast,lunch,dinner',
            'products' => 'nullable|array',
            'products.*.product_id' => 'required_with:products|exists:products,id',
            'products.*.quantity' => 'required_with:products|integer|min:1|max:999',
            'products.*.unit_price' => 'required_with:products|numeric|min:0|max:9999.99',
            'products.*.notes' => 'nullable|string|max:500',

            // Additional services validation
            'additional_services' => 'nullable|array',
            'additional_services.*.id' => 'required|exists:additional_services,id',
            'additional_services.*.quantity' => 'required|integer|min:1|max:999',
            'additional_services.*.price' => 'required|numeric|min:0|max:9999.99',
            'additional_services.*.is_per_day' => 'required|boolean',
            'additional_services.*.notes' => 'nullable|string|max:500',

            // Visitor information - nested validation
            'visitor' => 'required|array',
            'visitor.name' => 'required|string|max:255',
            'visitor.client_type_id' => 'required|exists:client_types,id',
            'visitor.id_type' => 'required|in:national_id,passport,military_id',
            'visitor.id_number' => 'required|string|max:255',
            'visitor.nationality' => 'required|string|max:255',
            'visitor.phone' => 'nullable|string|max:20',
            'visitor.emergency_contact' => 'nullable|string|max:255',

            // Other optional fields
            'notes' => 'nullable|string|max:1000',

            // Attachment rules
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240', // 10MB max
        ];
    }

    public function messages(): array
    {
        return [
            // Visitor validation messages
            'visitor.required' => 'بيانات الزائر مطلوبة',
            'visitor.array' => 'بيانات الزائر غير صحيحة',
            'visitor.name.required' => 'اسم الزائر مطلوب',
            'visitor.name.string' => 'اسم الزائر يجب أن يكون نص',
            'visitor.name.max' => 'اسم الزائر يجب أن لا يتجاوز 255 حرف',
            'visitor.client_type_id.required' => 'نوع العميل مطلوب',
            'visitor.client_type_id.exists' => 'نوع العميل غير موجود',
            'visitor.id_type.required' => 'نوع الهوية مطلوب',
            'visitor.id_type.string' => 'نوع الهوية يجب أن يكون نص',
            'visitor.id_number.required' => 'رقم الهوية مطلوب',
            'visitor.id_number.string' => 'رقم الهوية يجب أن يكون نص',
            'visitor.id_number.max' => 'رقم الهوية يجب أن لا يتجاوز 255 حرف',
            'visitor.nationality.required' => 'الجنسية مطلوبة',
            'visitor.nationality.string' => 'الجنسية يجب أن تكون نص',
            'visitor.nationality.max' => 'الجنسية يجب أن لا تتجاوز 255 حرف',
            'visitor.phone.string' => 'رقم الهاتف يجب أن يكون نص',
            'visitor.phone.max' => 'رقم الهاتف يجب أن لا يتجاوز 20 رقم',
            'visitor.emergency_contact.string' => 'رقم الطوارئ يجب أن يكون نص',
            'visitor.emergency_contact.max' => 'رقم الطوارئ يجب أن لا يتجاوز 20 رقم',

            // Booking validation messages
            'apartment_id.required' => 'الشقة مطلوبة',
            'apartment_id.exists' => 'الشقة غير موجودة',
            'arrival_datetime.required' => 'تاريخ الوصول مطلوب',
            'arrival_datetime.date' => 'تاريخ الوصول يجب أن يكون تاريخ صحيح',
            'checkout_datetime.required' => 'تاريخ المغادرة مطلوب',
            'checkout_datetime.date' => 'تاريخ المغادرة يجب أن يكون تاريخ صحيح',
            'checkout_datetime.after' => 'تاريخ المغادرة يجب أن يكون بعد تاريخ الوصول',
            'duration_days.required' => 'مدة الإقامة مطلوبة',
            'duration_days.integer' => 'مدة الإقامة يجب أن تكون رقم صحيح',
            'duration_days.min' => 'مدة الإقامة يجب أن تكون على الأقل يوم واحد',
            'duration_days.max' => 'مدة الإقامة لا يمكن أن تتجاوز 365 يوم',

            // Payment validation messages
            'total_amount.required' => 'المبلغ الإجمالي مطلوب',
            'total_amount.numeric' => 'المبلغ الإجمالي يجب أن يكون رقم',
            'total_amount.min' => 'المبلغ الإجمالي يجب أن يكون أكبر من أو يساوي 0',
            'deposit_amount.numeric' => 'مبلغ العربون يجب أن يكون رقم',
            'deposit_amount.min' => 'مبلغ العربون يجب أن يكون أكبر من أو يساوي 0',
            'payment_method.required' => 'طريقة الدفع مطلوبة',
            'payment_method.string' => 'طريقة الدفع يجب أن تكون نص',

            // Products validation messages
            'products.array' => 'المنتجات يجب أن تكون مصفوفة',
            'products.*.product_id.required_with' => 'معرف المنتج مطلوب',
            'products.*.product_id.exists' => 'المنتج غير موجود',
            'products.*.quantity.required_with' => 'كمية المنتج مطلوبة',
            'products.*.quantity.integer' => 'كمية المنتج يجب أن تكون رقم صحيح',
            'products.*.quantity.min' => 'كمية المنتج يجب أن تكون على الأقل 1',
            'products.*.quantity.max' => 'كمية المنتج لا يمكن أن تتجاوز 999',
            'products.*.unit_price.required_with' => 'سعر الوحدة مطلوب',
            'products.*.unit_price.numeric' => 'سعر الوحدة يجب أن يكون رقم',
            'products.*.unit_price.min' => 'سعر الوحدة يجب أن يكون أكبر من أو يساوي 0',
            'products.*.unit_price.max' => 'سعر الوحدة لا يمكن أن يتجاوز 9999.99',
            'products.*.notes.string' => 'ملاحظات المنتج يجب أن تكون نص',
            'products.*.notes.max' => 'ملاحظات المنتج يجب أن لا تتجاوز 500 حرف',

            // Meals validation messages
            'meals.array' => 'الوجبات يجب أن تكون مصفوفة',
            'meals.*.in' => 'نوع الوجبة غير صحيح',

            // Other validation messages
            'notes.string' => 'الملاحظات يجب أن تكون نص',
            'notes.max' => 'الملاحظات يجب أن لا تتجاوز 1000 حرف',

            // Additional services validation messages
            'additional_services.array' => 'الخدمات الإضافية يجب أن تكون مصفوفة',
            'additional_services.*.id.required' => 'معرف الخدمة الإضافية مطلوب',
            'additional_services.*.id.exists' => 'الخدمة الإضافية غير موجودة',
            'additional_services.*.quantity.required' => 'كمية الخدمة الإضافية مطلوبة',
            'additional_services.*.quantity.integer' => 'كمية الخدمة الإضافية يجب أن تكون رقم صحيح',
            'additional_services.*.quantity.min' => 'كمية الخدمة الإضافية يجب أن تكون على الأقل 1',
            'additional_services.*.quantity.max' => 'كمية الخدمة الإضافية لا يمكن أن تتجاوز 999',
            'additional_services.*.price.required' => 'سعر الخدمة الإضافية مطلوب',
            'additional_services.*.price.numeric' => 'سعر الخدمة الإضافية يجب أن يكون رقم',
            'additional_services.*.price.min' => 'سعر الخدمة الإضافية يجب أن يكون أكبر من أو يساوي 0',
            'additional_services.*.price.max' => 'سعر الخدمة الإضافية لا يمكن أن يتجاوز 9999.99',
            'additional_services.*.is_per_day.required' => 'نوع حساب سعر الخدمة الإضافية مطلوب',
            'additional_services.*.is_per_day.boolean' => 'نوع حساب سعر الخدمة الإضافية يجب أن يكون صحيح أو خطأ',
            'additional_services.*.notes.string' => 'ملاحظات الخدمة الإضافية يجب أن تكون نص',
            'additional_services.*.notes.max' => 'ملاحظات الخدمة الإضافية يجب أن لا تتجاوز 500 حرف',
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation()
    {
        // If booking data is sent as JSON string, decode it
        if ($this->has('booking_data') && is_string($this->booking_data)) {
            $bookingData = json_decode($this->booking_data, true);
            if (is_array($bookingData)) {
                // Merge the booking data directly - no need to modify visitor structure
                $this->merge($bookingData);
            }
        }

        // Handle attachments array
        if ($this->hasFile('attachments')) {
            $files = $this->file('attachments');
            if (!is_array($files)) {
                $files = [$files];
            }
            $this->merge(['attachments' => $files]);
        }

        // Ensure products array has proper structure
        if ($this->has('products') && is_array($this->products)) {
            $products = [];
            foreach ($this->products as $product) {
                if (isset($product['id'])) {
                    // Handle legacy format where 'id' is used instead of 'product_id'
                    $product['product_id'] = $product['id'];
                    unset($product['id']);
                }
                
                // Ensure required fields have default values
                $product['quantity'] = $product['quantity'] ?? 1;
                $product['unit_price'] = $product['unit_price'] ?? $product['price'] ?? 0;
                
                $products[] = $product;
            }
            
            $this->merge(['products' => $products]);
        }

        // Handle additional services array
        if ($this->has('additional_services') && is_array($this->additional_services)) {
            $services = [];
            foreach ($this->additional_services as $service) {
                if (isset($service['id'])) {
                    // Ensure required fields have default values
                    $service['quantity'] = $service['quantity'] ?? 1;
                    $service['price'] = $service['price'] ?? 0;
                    $service['is_per_day'] = $service['is_per_day'] ?? true;

                    $services[] = $service;
                }
            }

            $this->merge(['additional_services' => $services]);
        }
    }
}