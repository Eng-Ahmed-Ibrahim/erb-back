<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Booking extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'id',
        'visitor_id',
        'apartment_id',
        'arrival_datetime',
        'checkout_datetime',
        'duration_days',
        'meals',
        'total_amount',
        'deposit_amount',
        'remaining_amount',
        'checkout_discount_amount',
        'checkout_discount_reason',
        'final_amount',
        'payment_status',
        'payment_method',
        'payment_method_id',
        'notes',
        'status',
        'created_by',
        'early_checkout_reason'
    ];

    protected $casts = [
        'arrival_datetime' => 'datetime',
        'checkout_datetime' => 'datetime',
        'meals' => 'array',
        'duration_days' => 'integer',
        'total_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'checkout_discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
    ];

    protected $attributes = [
        'status' => 'active',
        'total_amount' => 0,
        'deposit_amount' => 0,
        'remaining_amount' => 0,
        'checkout_discount_amount' => 0,
        'final_amount' => 0,
        'payment_status' => 'pending',
        'meals' => '[]',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public static $statuses = [
        self::STATUS_PENDING => 'محجوز (في الانتظار)',
        self::STATUS_CONFIRMED => 'مؤكد',
        self::STATUS_ACTIVE => 'نشط',
        self::STATUS_COMPLETED => 'مكتمل',
        self::STATUS_CANCELLED => 'ملغي',
    ];

    // Meal type constants
    const MEAL_BREAKFAST = 'breakfast';
    const MEAL_LUNCH = 'lunch';
    const MEAL_DINNER = 'dinner';

    // Payment status constants
    const PAYMENT_STATUS_PENDING = 'pending';
    const PAYMENT_STATUS_PARTIAL = 'partial';
    const PAYMENT_STATUS_COMPLETED = 'completed';

    public static $paymentStatuses = [
        self::PAYMENT_STATUS_PENDING => 'في الانتظار',
        self::PAYMENT_STATUS_PARTIAL => 'دفع جزئي',
        self::PAYMENT_STATUS_COMPLETED => 'مكتمل',
    ];

    /**
     * Relationships
     */
    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }

    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function bookingProducts()
    {
        return $this->hasMany(BookingProduct::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'booking_products')
            ->withPivot(['quantity', 'unit_price', 'total_price', 'notes'])
            ->withTimestamps();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function additionalServices()
    {
        return $this->belongsToMany(AdditionalService::class, 'booking_additional_services')
            ->withPivot(['quantity', 'price', 'notes'])
            ->withTimestamps();
    }

    /**
     * Accessors
     */
    public function getExpectedCheckoutAttribute()
    {
        if ($this->arrival_datetime && $this->duration_days) {
            return $this->arrival_datetime->addDays($this->duration_days);
        }
        return null;
    }

    public function getMealCountAttribute()
    {
        return is_array($this->meals) ? count($this->meals) : 0;
    }

    public function getProductCountAttribute()
    {
        return $this->bookingProducts()->sum('quantity');
    }

    public function getProductsTotalAttribute()
    {
        return $this->bookingProducts()->sum('total_price');
    }

    public function getStatusTextAttribute()
    {
        return self::$statuses[$this->status] ?? 'غير محدد';
    }

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            self::STATUS_PENDING => '#fa8c16',
            self::STATUS_CONFIRMED => '#1890ff',
            self::STATUS_ACTIVE => '#52c41a',
            self::STATUS_COMPLETED => '#13c2c2',
            self::STATUS_CANCELLED => '#ff4d4f',
            default => '#d9d9d9'
        };
    }

    public function getPaymentStatusTextAttribute()
    {
        return self::$paymentStatuses[$this->payment_status] ?? 'غير محدد';
    }

    public function getPaymentStatusColorAttribute()
    {
        return match ($this->payment_status) {
            self::PAYMENT_STATUS_PENDING => '#fa8c16',
            self::PAYMENT_STATUS_PARTIAL => '#1890ff',
            self::PAYMENT_STATUS_COMPLETED => '#52c41a',
            default => '#d9d9d9'
        };
    }

    public function getOutstandingAmountAttribute()
    {
        return max(0, $this->remaining_amount - $this->checkout_discount_amount);
    }

    public function getDepositPercentageAttribute()
    {
        if ($this->total_amount <= 0) {
            return 0;
        }
        return round(($this->deposit_amount / $this->total_amount) * 100, 2);
    }

    /**
     * Helper methods
     */
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmed()
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isCheckedOut()
    {
        return !is_null($this->checkout_datetime);
    }

    public function getDurationInDays()
    {
        if ($this->checkout_datetime) {
            return Carbon::parse($this->checkout_datetime)->diffInDays(Carbon::parse($this->arrival_datetime));
        }

        return $this->duration_days;
    }

    public function getRemainingDays()
    {
        if ($this->isCompleted() || $this->isCancelled()) {
            return 0;
        }

        $checkoutDate = Carbon::parse($this->arrival_datetime)->addDays($this->duration_days);
        return max(0, $checkoutDate->diffInDays(Carbon::now(), false));
    }

    public function hasMeal($mealType)
    {
        return in_array($mealType, $this->meals ?? []);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    /**
     * Payment status helper methods
     */
    public function isPaymentPending()
    {
        return $this->payment_status === self::PAYMENT_STATUS_PENDING;
    }

    public function isPaymentPartial()
    {
        return $this->payment_status === self::PAYMENT_STATUS_PARTIAL;
    }

    public function isPaymentCompleted()
    {
        return $this->payment_status === self::PAYMENT_STATUS_COMPLETED;
    }

    public function hasDeposit()
    {
        return $this->deposit_amount > 0;
    }

    public function hasOutstandingAmount()
    {
        return $this->outstanding_amount > 0;
    }

    public function hasCheckoutDiscount()
    {
        return $this->checkout_discount_amount > 0;
    }

    /**
     * Actions
     */
    public function checkout($checkoutDateTime = null)
    {
        $this->update([
            'checkout_datetime' => $checkoutDateTime ?? Carbon::now(),
            'status' => self::STATUS_COMPLETED
        ]);

        // Mark apartment as available
        $this->apartment->markAsAvailable();

        return $this;
    }

    public function confirm()
    {
        if (!$this->isPending()) {
            throw new \Exception('Only pending bookings can be confirmed');
        }

        $this->update([
            'status' => self::STATUS_CONFIRMED
        ]);

        return $this;
    }

    public function activate()
    {
        if (!$this->isPending() && !$this->isConfirmed()) {
            throw new \Exception('Only pending or confirmed bookings can be activated');
        }

        $this->update([
            'status' => self::STATUS_ACTIVE
        ]);

        // Mark apartment as occupied when activating
        $this->apartment->markAsOccupied();

        return $this;
    }

    public function cancel($reason = null)
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'notes' => $reason ? $this->notes . "\nملغي: " . $reason : $this->notes
        ]);

        // Mark apartment as available
        $this->apartment->markAsAvailable();

        return $this;
    }

    public function complete()
    {
        return $this->checkout();
    }

    /**
     * Calculate and set deposit and remaining amounts
     */
    public function calculateDepositAmounts($depositAmount = null)
    {
        if ($depositAmount !== null) {
            $this->deposit_amount = min($depositAmount, $this->total_amount);
        }

        $this->remaining_amount = max(0, $this->total_amount - $this->deposit_amount);
        $this->final_amount = $this->total_amount;

        // Update payment status
        if ($this->deposit_amount <= 0) {
            $this->payment_status = self::PAYMENT_STATUS_PENDING;
        } elseif ($this->deposit_amount >= $this->total_amount) {
            $this->payment_status = self::PAYMENT_STATUS_COMPLETED;
        } else {
            $this->payment_status = self::PAYMENT_STATUS_PARTIAL;
        }

        return $this;
    }

    /**
     * Apply checkout discount
     */
    public function applyCheckoutDiscount($discountAmount, $reason = null)
    {
        $this->checkout_discount_amount = min($discountAmount, $this->remaining_amount);
        $this->checkout_discount_reason = $reason;

        // Recalculate final amount
        $this->final_amount = $this->total_amount - $this->checkout_discount_amount;

        // Update remaining amount after discount
        $this->remaining_amount = max(0, $this->remaining_amount - $this->checkout_discount_amount);

        // Update payment status if remaining amount is now zero
        if ($this->remaining_amount <= 0) {
            $this->payment_status = self::PAYMENT_STATUS_COMPLETED;
        }

        return $this;
    }

    /**
     * Complete final payment at checkout
     */
    public function completePayment($finalPaymentAmount = null, $discountAmount = 0, $discountReason = null)
    {
        // Apply checkout discount if provided
        if ($discountAmount > 0) {
            $this->applyCheckoutDiscount($discountAmount, $discountReason);
        }

        // Mark payment as completed
        $this->payment_status = self::PAYMENT_STATUS_COMPLETED;
        $this->remaining_amount = 0;

        return $this;
    }

    /**
     * Calculate total amount based on services and client type pricing
     */
    public function calculateAmount()
    {
        $amount = 0;

        // Base apartment rate - use dynamic pricing based on client type
        if ($this->visitor && $this->visitor->client_type_id && $this->apartment) {
            $apartmentPrice = $this->apartment->calculateTotalPrice($this->visitor->client_type_id, $this->duration_days);
            $amount += $apartmentPrice;
        } else {
            // Fallback to old pricing structure
            $amount += ($this->apartment->daily_rate ?? 0) * $this->duration_days;
        }

        // Meal charges (example pricing)
        $mealPrices = [
            'breakfast' => 15,
            'lunch' => 25,
            'dinner' => 30
        ];

        foreach ($this->meals ?? [] as $meal) {
            if (isset($mealPrices[$meal])) {
                $amount += $mealPrices[$meal] * $this->duration_days;
            }
        }

        // Product charges from pivot table
        $amount += $this->products_total;

        return round($amount, 2);
    }

    /**
     * Apply discount based on visitor's client type
     */
    public function applyClientTypeDiscount()
    {
        if (!$this->visitor || !$this->visitor->clientType) {
            return $this->total_amount;
        }

        $discount = $this->visitor->clientType->discount ?? 0;
        $discountAmount = ($this->total_amount * $discount) / 100;

        return round($this->total_amount - $discountAmount, 2);
    }

    /**
     * Calculate actual stayed days for early checkout
     */
    public function calculateActualStayedDays($checkoutDateTime = null)
    {
        $checkoutTime = $checkoutDateTime ? Carbon::parse($checkoutDateTime) : Carbon::now();
        $arrivalTime = Carbon::parse($this->arrival_datetime);

        // Calculate actual days stayed (including partial days)
        return max(1, ceil($checkoutTime->diffInHours($arrivalTime) / 24));
    }

    /**
     * Calculate adjusted amount for early checkout based on actual stayed days
     */
    public function calculateEarlyCheckoutAmount($checkoutDateTime = null)
    {
        $actualDays = $this->calculateActualStayedDays($checkoutDateTime);
        $dailyRate = $this->total_amount / $this->duration_days;

        // Calculate new total based on actual days
        $adjustedTotal = $dailyRate * $actualDays;

        return round($adjustedTotal, 2);
    }

    /**
     * Calculate early checkout refund amount
     */
    public function calculateEarlyCheckoutRefund($checkoutDateTime = null)
    {
        $adjustedAmount = $this->calculateEarlyCheckoutAmount($checkoutDateTime);
        return max(0, $this->total_amount - $adjustedAmount);
    }

    /**
     * Calculate deposit refund for early checkout
     */
    public function calculateDepositRefund($checkoutDateTime = null)
    {
        $adjustedAmount = $this->calculateEarlyCheckoutAmount($checkoutDateTime);

        // If deposit is more than adjusted amount, calculate refund
        if ($this->deposit_amount > $adjustedAmount) {
            return round($this->deposit_amount - $adjustedAmount, 2);
        }

        return 0;
    }

    /**
     * Apply early checkout adjustments
     */
    public function applyEarlyCheckoutAdjustments($checkoutDateTime = null)
    {
        $actualDays = $this->calculateActualStayedDays($checkoutDateTime);
        $adjustedAmount = $this->calculateEarlyCheckoutAmount($checkoutDateTime);
        $refundAmount = $this->calculateEarlyCheckoutRefund($checkoutDateTime);
        $depositRefund = $this->calculateDepositRefund($checkoutDateTime);

        // Update booking amounts
        $this->duration_days = $actualDays;
        $this->total_amount = $adjustedAmount;

        // Handle deposit scenarios
        if ($depositRefund > 0) {
            // Case: Deposit was more than adjusted amount
            $this->remaining_amount = 0; // Nothing more to pay
            $this->checkout_discount_amount = $refundAmount;
            $this->checkout_discount_reason = sprintf(
                'تعديل المبلغ للمغادرة المبكرة (مبلغ مسترد: %s)',
                $depositRefund
            );
        } else {
            // Normal case: Calculate remaining after deposit
            $this->remaining_amount = max(0, $adjustedAmount - $this->deposit_amount);
            if ($refundAmount > 0) {
                $this->checkout_discount_amount = $refundAmount;
                $this->checkout_discount_reason = 'تعديل المبلغ للمغادرة المبكرة';
            }
        }

        $this->final_amount = $adjustedAmount;

        return $this;
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            // Mark apartment as occupied only for active bookings
            if ($booking->apartment && $booking->status !== self::STATUS_PENDING) {
                $booking->apartment->markAsOccupied();
            }
        });

        static::updating(function ($booking) {
            // Recalculate amount if relevant fields changed
            if ($booking->isDirty(['duration_days', 'meals'])) {
                $booking->total_amount = $booking->calculateAmount();
            }

            // Recalculate deposit amounts if total_amount or deposit_amount changed
            if ($booking->isDirty(['total_amount', 'deposit_amount'])) {
                $booking->calculateDepositAmounts($booking->deposit_amount);
            }
        });
    }
}