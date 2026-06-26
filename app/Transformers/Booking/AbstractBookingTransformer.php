<?php

namespace App\Transformers\Booking;

use App\Models\Booking;
use App\Transformers\BaseTransformer;

class AbstractBookingTransformer extends BaseTransformer
{
    protected $relations = [];

    protected $load = [];

    public static function transform(Booking $booking)
    {
        return [
            'id' => (string) $booking->id,
            'visitor_id' => (string) $booking->visitor_id,
            'apartment' => $booking->apartment,
            'visitor' => $booking->visitor,
            'arrival_datetime' => $booking->arrival_datetime?->format('Y-m-d H:i:s'),
            'checkout_datetime' => $booking->checkout_datetime?->format('Y-m-d H:i:s'),
            'expected_checkout' => $booking->expected_checkout?->format('Y-m-d H:i:s'),
            'duration_days' => $booking->duration_days,
            'meals' => $booking->meals ?? [],
            'meal_count' => $booking->meal_count,
            'product_count' => $booking->product_count,
            'products_total' => (float) $booking->products_total,
            'total_amount' => (float) $booking->total_amount,
            'payment_method' => $booking->payment_method,
            'notes' => $booking->notes,
            'status' => $booking->status,
            'status_text' => $booking->status_text,
            'status_color' => $booking->status_color,
            'is_pending' => $booking->isPending(),
            'is_confirmed' => $booking->isConfirmed(),
            'is_active' => $booking->isActive(),
            'is_completed' => $booking->isCompleted(),
            'is_cancelled' => $booking->isCancelled(),
            'is_checked_out' => $booking->isCheckedOut(),
            'duration_in_days' => $booking->getDurationInDays(),
            'remaining_days' => $booking->getRemainingDays(),
            'created_at' => $booking->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $booking->updated_at?->format('Y-m-d H:i:s'),
            'payment_status' => $booking->payment_status,
            'deposit_amount' => $booking->deposit_amount,
            'remaining_amount' => $booking->remaining_amount,
            'checkout_discount_amount' => $booking->checkout_discount_amount,
            'checkout_discount_reason' => $booking->checkout_discount_reason,
            'final_amount' => $booking->final_amount,
        ];
    }
}