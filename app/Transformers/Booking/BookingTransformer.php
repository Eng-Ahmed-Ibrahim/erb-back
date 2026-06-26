<?php

namespace App\Transformers\Booking;

use App\Models\Booking;
use App\Transformers\BaseTransformer;
use App\Transformers\Visitor\AbstractVisitorTransformer;
use App\Transformers\Apartment\AbstractApartmentTransformer;
use App\Transformers\Attachment\AbstractAttachmentTransformer;

class BookingTransformer extends AbstractBookingTransformer
{
    protected $relations = ['visitor', 'apartment', 'attachments', 'bookingProducts'];

    protected $load = [];

    public static function transform(Booking $booking)
    {
        $data = parent::transform($booking);

        // Add related data
        $data['visitor'] = $booking->visitor
            ? AbstractVisitorTransformer::transform($booking->visitor)
            : null;

        $data['apartment'] = $booking->apartment
            ? AbstractApartmentTransformer::transform($booking->apartment)
            : null;

        $data['attachments'] = $booking->attachments
            ? $booking->attachments->map(function ($attachment) {
                return AbstractAttachmentTransformer::transform($attachment);
            })->toArray()
            : [];

        // Detailed product information from pivot table
        $data['booking_products'] = $booking->bookingProducts
            ? $booking->bookingProducts->map(function ($bookingProduct) {
                return [
                    'id' => (string) $bookingProduct->id,
                    'product_id' => (string) $bookingProduct->product_id,
                    'product_name' => $bookingProduct->product?->name ?? 'منتج غير محدد',
                    'quantity' => $bookingProduct->quantity,
                    'unit_price' => (float) $bookingProduct->unit_price,
                    'total_price' => (float) $bookingProduct->total_price,
                    'notes' => $bookingProduct->notes,
                    'formatted_unit_price' => $bookingProduct->formatted_unit_price,
                    'formatted_total_price' => $bookingProduct->formatted_total_price,
                ];
            })->toArray()
            : [];

        // Meal information with Arabic names
        $data['meals_formatted'] = self::formatMeals($booking->meals ?? []);

        // Building information from apartment
        if ($booking->apartment && $booking->apartment->building) {
            $data['building'] = [
                'id' => (string) $booking->apartment->building->id,
                'name' => $booking->apartment->building->name,
                'color' => $booking->apartment->building->color ?? '#1890ff',
            ];
        }

        // Status information
        $data['status_text'] = self::getStatusText($booking->status);
        $data['status_color'] = self::getStatusColor($booking->status);

        // Time calculations
        if ($booking->arrival_datetime && $booking->checkout_datetime) {
            $data['actual_duration_days'] = $booking->arrival_datetime->diffInDays($booking->checkout_datetime);
            $data['duration_hours'] = $booking->arrival_datetime->diffInHours($booking->checkout_datetime);
        }

        if ($booking->arrival_datetime) {
            $data['days_since_arrival'] = $booking->arrival_datetime->diffInDays(now());
        }

        // Financial summary
        $data['financial_summary'] = [
            'base_amount' => (float) $booking->total_amount,
            'products_total' => (float) $booking->products_total,
            'discount_amount' => (float) ($booking->total_amount - $booking->applyClientTypeDiscount()),
            'final_amount' => (float) $booking->applyClientTypeDiscount(),
            'currency' => 'SAR'
        ];

        $data['created_by_name'] = $booking->createdBy?->name ?? 'غير محدد';

        // Include additional services if loaded
        if ($booking->relationLoaded('additionalServices')) {
            $data['additional_services'] = $booking->additionalServices->map(function ($service) use ($booking) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'code' => $service->code,
                    'price' => $service->pivot->price,
                    'quantity' => $service->pivot->quantity,
                    'is_per_day' => $service->is_per_day,
                    'notes' => $service->pivot->notes,
                    'total_amount' => $service->is_per_day
                        ? $service->pivot->price * $service->pivot->quantity * $booking->duration_days
                        : $service->pivot->price * $service->pivot->quantity,
                ];
            });
        }

        return $data;
    }

    private static function formatMeals(array $meals): array
    {
        $mealNames = [
            Booking::MEAL_BREAKFAST => 'إفطار',
            Booking::MEAL_LUNCH => 'غداء',
            Booking::MEAL_DINNER => 'عشاء'
        ];

        return array_map(function ($meal) use ($mealNames) {
            return [
                'key' => $meal,
                'name' => $mealNames[$meal] ?? $meal,
            ];
        }, $meals);
    }

    private static function getStatusText(string $status): string
    {
        return match ($status) {
            Booking::STATUS_PENDING => 'في الانتظار',
            Booking::STATUS_CONFIRMED => 'مؤكد',
            Booking::STATUS_ACTIVE => 'نشط',
            Booking::STATUS_COMPLETED => 'مكتمل',
            Booking::STATUS_CANCELLED => 'ملغي',
            default => 'غير محدد'
        };
    }

    private static function getStatusColor(string $status): string
    {
        return match ($status) {
            Booking::STATUS_PENDING => '#fa8c16',
            Booking::STATUS_CONFIRMED => '#1890ff',
            Booking::STATUS_ACTIVE => '#52c41a',
            Booking::STATUS_COMPLETED => '#13c2c2',
            Booking::STATUS_CANCELLED => '#ff4d4f',
            default => '#d9d9d9'
        };
    }
}