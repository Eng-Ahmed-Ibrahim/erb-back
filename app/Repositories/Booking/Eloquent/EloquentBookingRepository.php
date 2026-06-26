<?php

namespace App\Repositories\Booking\Eloquent;

use App\Models\Booking;
use App\Models\BookingProduct;
use App\Models\Visitor;
use App\Repositories\Booking\BookingRepository;
use App\Repositories\EloquentBaseRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class EloquentBookingRepository extends EloquentBaseRepository implements BookingRepository
{
    public function __construct()
    {
        parent::__construct(new Booking);
    }

    public function adminCreate($data)
    {
        return DB::transaction(function () use ($data) {

            // Handle visitor creation if visitor_id is not provided
            if (!isset($data['visitor_id']) && isset($data['visitor'])) {
                $visitor = Visitor::create($data['visitor']);
                $data['visitor_id'] = $visitor->id;
                unset($data['visitor']);  // Remove visitor data from booking data
            }

            // Calculate checkout_datetime if not provided
            if (!isset($data['checkout_datetime']) && isset($data['arrival_datetime'], $data['duration_days'])) {
                $arrivalDate = Carbon::parse($data['arrival_datetime']);
                $data['checkout_datetime'] = $arrivalDate->addDays($data['duration_days']);
            }

            // Extract products data before creating booking
            $products = [];
            if (isset($data['products'])) {
                $products = $data['products'];
                unset($data['products']);
            }

            // Remove attachments from data if present
            if (isset($data['attachments'])) {
                Log::info('Attaceeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeehments:', $data['attachments']);
                unset($data['attachments']);
            }

            // Set created_by field automatically
            if (!isset($data['created_by']) && auth()->check()) {
                $data['created_by'] = auth()->id();
            }
            // Create booking
            $booking = $this->create($data);

            if (!$booking) {
                throw new \Exception('Failed to create booking');
            }

            // Handle products - create BookingProduct records
            if (!empty($products)) {
                $this->attachProductsToBooking($booking, $products);
            }

            // Mark apartment as occupied
            if ($booking->apartment) {
                $booking->apartment->markAsOccupied();
            }

            // Recalculate total amount including products
            $booking->total_amount = $booking->calculateAmount();

            // Handle deposit calculations if deposit_amount is provided
            if (isset($data['deposit_amount']) && $data['deposit_amount'] > 0) {
                $booking->calculateDepositAmounts($data['deposit_amount']);
            } else {
                // If no deposit, set remaining amount equal to total amount
                $booking->remaining_amount = $booking->total_amount;
                $booking->final_amount = $booking->total_amount;
                $booking->payment_status = 'pending';
            }

            // Save the changes
            $saved = $booking->save();

            if (!$saved) {
                throw new \Exception('Failed to save booking');
            }

            return $booking->fresh(['visitor', 'apartment', 'bookingProducts', 'attachments']);
        });
    }

    /**
     * Attach products to booking via pivot table
     */
    private function attachProductsToBooking(Booking $booking, array $products)
    {

        foreach ($products as $product) {
            $bookingProduct = BookingProduct::create([
                'booking_id' => $booking->id,
                'product_id' => $product['product_id'],
                'quantity' => $product['quantity'],
                'unit_price' => $product['unit_price'],
                'notes' => $product['notes'] ?? null,
            ]);
        }
    }

    public function adminUpdate($model, $data)
    {
        return DB::transaction(function () use ($model, $data) {
            // Extract and remove products from incoming data
            $products = $data['products'] ?? [];
            unset($data['products']);

            // Fill and save model data
            $model->fill($data);
            $model->save();

            if (!empty($products)) {
                // Remove existing products
                $model->bookingProducts()->delete();

                foreach ($products as $product) {
                    $model->bookingProducts()->create($product);
                }

                $model->total_amount = $model->calculateAmount();
                $model->save();
            }

            // Return model with required relationships loaded
            return $model->fresh(['visitor', 'apartment', 'bookingProducts']);
        });
    }


    public function adminDelete($model)
    {
        return DB::transaction(function () use ($model) {
            // Mark apartment as available if deleting active booking
            if ($model->isActive() && $model->apartment) {
                $model->apartment->markAsAvailable();
            }

            // Delete related BookingProduct records (cascade should handle this)
            $model->bookingProducts()->delete();

            return $this->delete($model);
        });
    }

    public function checkoutBooking($bookingId, $checkoutData = [])
    {
        return DB::transaction(function () use ($bookingId, $checkoutData) {
            $booking = $this->find($bookingId);

            if (!$booking) {
                throw new \Exception('Booking not found');
            }

            if (!$booking->isActive()) {
                throw new \Exception('Booking is not active');
            }

            // Determine if this is an early checkout
            $actualCheckoutTime = Carbon::now()->toDateTimeString();
            $scheduledCheckoutTime = new \DateTime($booking->checkout_datetime);
            $isEarlyCheckout = $actualCheckoutTime < $scheduledCheckoutTime;

            // Update booking with checkout data
            if (isset($checkoutData['actual_checkout_datetime'])) {
                $booking->actual_checkout_datetime =  $actualCheckoutTime;
            }

            if (isset($checkoutData['notes'])) {
                $booking->checkout_notes = $checkoutData['notes'];
            }

            if (isset($checkoutData['early_checkout_reason']) && $isEarlyCheckout) {
                $booking->early_checkout_reason = $checkoutData['early_checkout_reason'];
            }

            // Mark as early checkout if applicable
            $booking->is_early_checkout = $isEarlyCheckout;

            // Handle checkout discount if provided
            if (isset($checkoutData['discount_amount']) && $checkoutData['discount_amount'] > 0) {
                $booking->applyCheckoutDiscount(
                    $checkoutData['discount_amount'],
                    $checkoutData['discount_reason'] ?? 'خصم عند الخروج'
                );
            }

            // Complete payment and booking
            $booking->completePayment(
                $checkoutData['final_amount'] ?? null,
                $checkoutData['checkout_discount_amount'] ?? 0,
                $checkoutData['checkout_discount_reason'] ?? null
            );

            // Complete the booking
            $booking->checkout();

            return $booking->fresh(['visitor', 'apartment', 'bookingProducts']);
        });
    }

    public function getActiveBookings()
    {
        return $this
            ->model
            ->with(['visitor', 'apartment', 'bookingProducts.product'])
            ->where('status', Booking::STATUS_ACTIVE)
            ->orderBy('arrival_datetime', 'desc')
            ->get();
    }

    public function getBookingsByVisitor($visitorId)
    {
        return $this
            ->model
            ->with(['apartment', 'bookingProducts.product'])
            ->where('visitor_id', $visitorId)
            ->orderBy('arrival_datetime', 'desc')
            ->get();
    }

    public function getBookingsByApartment($apartmentId)
    {
        return $this
            ->model
            ->with(['visitor', 'bookingProducts.product'])
            ->where('apartment_id', $apartmentId)
            ->orderBy('arrival_datetime', 'desc')
            ->get();
    }

    public function getBookingsByDateRange($startDate, $endDate)
    {
        return $this
            ->model
            ->with(['visitor', 'apartment', 'bookingProducts.product'])
            ->whereBetween('arrival_datetime', [$startDate, $endDate])
            ->orderBy('arrival_datetime', 'desc')
            ->get();
    }

    public function getDashboardSummary()
    {
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        return [
            'total_bookings' => $this->model->count(),
            'active_bookings' => $this->model->where('status', Booking::STATUS_ACTIVE)->count(),
            'completed_bookings' => $this->model->where('status', Booking::STATUS_COMPLETED)->count(),
            'cancelled_bookings' => $this->model->where('status', Booking::STATUS_CANCELLED)->count(),
            'today_arrivals' => $this->model->whereDate('arrival_datetime', $today)->count(),
            'today_checkouts' => $this->model->whereDate('checkout_datetime', $today)->count(),
            'week_bookings' => $this->model->whereBetween('arrival_datetime', [$thisWeek, $thisWeek->copy()->endOfWeek()])->count(),
            'month_bookings' => $this->model->whereBetween('arrival_datetime', [$thisMonth, $thisMonth->copy()->endOfMonth()])->count(),
            'total_revenue' => $this->model->where('status', Booking::STATUS_COMPLETED)->sum('total_amount'),
            'month_revenue' => $this
                ->model
                ->where('status', Booking::STATUS_COMPLETED)
                ->whereBetween('arrival_datetime', [$thisMonth, $thisMonth->copy()->endOfMonth()])
                ->sum('total_amount'),
        ];
    }

    public function filterBookings(array $filters)
    {
        $query = $this->model->with(['visitor', 'apartment.building', 'bookingProducts.product']);

        if (isset($filters['client_type_id'])) {
            $query->whereHas('visitor', function ($q) use ($filters) {
                $q->where('client_type_id', $filters['client_type_id']);
            });
        }

        if (isset($filters['building_id'])) {
            $query->whereHas('apartment', function ($q) use ($filters) {
                $q->where('building_id', $filters['building_id']);
            });
        }

        if (isset($filters['room_type'])) {
            $query->whereHas('apartment', function ($q) use ($filters) {
                $q->where('room_type', $filters['room_type']);
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['arrival_date_from'])) {
            $query->whereDate('arrival_datetime', '>=', $filters['arrival_date_from']);
        }

        if (isset($filters['arrival_date_to'])) {
            $query->whereDate('arrival_datetime', '<=', $filters['arrival_date_to']);
        }

        if (isset($filters['meals'])) {
            $query->whereJsonContains('meals', $filters['meals']);
        }

        return $query->orderBy('arrival_datetime', 'desc')->get();
    }

    /**
     * Get booking with all relationships loaded
     */
    public function find($id)
    {
        return $this->model->with([
            'visitor',
            'apartment.building',
            'bookingProducts.product',
            'attachments'
        ])->find($id);
    }

    /** Get all bookings with relationships */

    /**
     * Get all bookings with relationships
     */
    public function all($orderBy = null, $sortedBy = 'desc'): Collection
    {
        if ($orderBy != null) {
            return $this->model->with([
                'visitor',
                'apartment.building',
                'bookingProducts.product'
            ])->orderBy($orderBy, $sortedBy)->get();
        }

        return $this->model->with([
            'visitor',
            'apartment.building',
            'bookingProducts.product'
        ])->get();
    }
}
