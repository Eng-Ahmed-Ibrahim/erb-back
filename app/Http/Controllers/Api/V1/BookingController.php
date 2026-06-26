<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Repositories\Booking\BookingRepository;
use App\Repositories\Attachment\AttachmentRepository;
use App\Transformers\Booking\AbstractBookingTransformer;
use App\Transformers\Booking\BookingTransformer;
use App\Transformers\BookingProduct\BookingProductTransformer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Models\AdditionalService;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use App\Models\Visitor;

class BookingController extends Controller
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private AttachmentRepository $attachmentRepository
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->attachmentRepository = $attachmentRepository;
    }

    public function index(Request $request)
    {
        $filters = $request->only([
            'visitor_type',
            'client_type_id',
            'building_id',
            'room_type',
            'status',
            'arrival_date_from',
            'arrival_date_to',
            'meals'
        ]);

        if (!empty(array_filter($filters))) {
            $data = $this->bookingRepository->filterBookings($filters);
        } else {
            $data = $this->bookingRepository->all('arrival_datetime', 'desc');
        }

        $data = $this->bookingRepository->paginate($data);

        return responder()->success($data, AbstractBookingTransformer::class)->respond(Response::HTTP_OK);
    }

    public function show(string $id)
    {
        $data = $this->bookingRepository->find($id);

        if (!$data) {
            return responder()->error('Booking not found')->respond(Response::HTTP_NOT_FOUND);
        }

        return responder()->success($data, BookingTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                // Visitor information
                'visitor.name' => 'required|string|max:255',
                'visitor.client_type_id' => 'required|exists:client_types,id',
                'visitor.id_type' => 'required|string',
                'visitor.id_number' => 'required|string|max:255',
                'visitor.nationality' => 'required|string|max:255',
                'visitor.phone' => 'nullable|string|max:20',
                'visitor.emergency_contact' => 'nullable|string|max:20',

                // Apartment and basic booking info
                'apartment_id' => 'required|exists:apartments,id',
                'arrival_datetime' => 'required|date',
                'checkout_datetime' => 'required|date|after:arrival_datetime',
                'duration_days' => 'required|integer|min:1',

                // Payment and amount info
                'total_amount' => 'required|numeric|min:0',
                'deposit_amount' => 'nullable|numeric|min:0',
                'payment_method' => 'required|string',
                'payment_status' => 'nullable|string',
                'status' => 'nullable|string',
                'notes' => 'nullable|string',

                // Optional arrays
                'meals' => 'nullable|array',
                'meals.*' => 'in:breakfast,lunch,dinner',
                'products' => 'nullable|array',
                'products.*.product_id' => 'required_with:products|exists:products,id',
                'products.*.quantity' => 'required_with:products|integer|min:1|max:999',
                'products.*.unit_price' => 'required_with:products|numeric|min:0|max:9999.99',
                'products.*.notes' => 'nullable|string|max:500',
            ]);

            // Start transaction
            DB::beginTransaction();

            try {
                // Create or update visitor
                $visitorData = $request->input('visitor');
                $visitor = Visitor::updateOrCreate(
                    ['id_number' => $visitorData['id_number']],
                    $visitorData
                );

                // Create booking
                $bookingData = $request->except('visitor', 'products', 'meals');
                $bookingData['visitor_id'] = $visitor->id;
                $bookingData['created_by'] = auth()->id();
                $booking = $this->bookingRepository->create($bookingData);

                // Attach products if any
                if ($request->has('products')) {
                    foreach ($request->products as $product) {
                        $productModel = Product::find($product['product_id']);
                        $booking->products()->attach($product['product_id'], [
                            'quantity' => $product['quantity'],
                            'unit_price' => $product['unit_price'],
                            'notes' => $product['notes'] ?? null,
                        ]);
                    }
                }

                // Attach meals if any
                if ($request->has('meals')) {
                    $booking->meals = $request->meals;
                    $booking->save();
                }

                // Attach additional services if any
                if ($request->has('additional_services')) {
                    foreach ($request->additional_services as $service) {
                        $additionalService = AdditionalService::find($service['id']);
                        if ($additionalService) {
                            $booking->additionalServices()->attach($service['id'], [
                                'quantity' => $service['quantity'],
                                'price' => $service['price'],
                                'notes' => $service['notes'] ?? ($service['is_per_day'] ? 'سعر يومي' : 'سعر ثابت'),
                            ]);
                        }
                    }
                }

                DB::commit();
                return responder()->success($booking)->respond();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return responder()->error($e->getMessage())->respond();
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $request->validate([
                'apartment_id' => 'nullable|exists:apartments,id',
                'visitor_id' => 'nullable|exists:visitors,id',
                'arrival_datetime' => 'nullable|date',
                'checkout_datetime' => 'nullable|date|after:arrival_datetime',
                'duration_days' => 'nullable|integer|min:1',
                'total_amount' => 'nullable|numeric|min:0',
                'deposit_amount' => 'nullable|numeric|min:0',
                'remaining_amount' => 'nullable|numeric|min:0',
                'payment_method' => 'nullable|string',
                'payment_status' => 'nullable|string',
                'status' => 'nullable|string',
                'notes' => 'nullable|string',
                'additional_services' => 'nullable|array',
                'additional_services.*.id' => 'required|exists:additional_services,id',
                'additional_services.*.quantity' => 'required|integer|min:1',
                'products' => 'nullable|array',
                'products.*.id' => 'required|exists:products,id',
                'products.*.quantity' => 'required|integer|min:1',
            ]);

            $booking = $this->bookingRepository->find($id);
            if (!$booking) {
                return responder()->error('Booking not found')->respond(Response::HTTP_NOT_FOUND);
            }

            $booking = $this->bookingRepository->update($id, $request->all());

            // Update additional services
            if ($request->has('additional_services')) {
                // Remove existing services
                $booking->additionalServices()->detach();

                // Attach new services with their current prices
                foreach ($request->additional_services as $service) {
                    $additionalService = AdditionalService::find($service['id']);
                    $booking->additionalServices()->attach($service['id'], [
                        'quantity' => $service['quantity'],
                        'price' => $additionalService->price,
                        'notes' => $additionalService->is_per_day ? 'سعر يومي' : 'سعر ثابت',
                    ]);
                }
            }

            // Update products
            if ($request->has('products')) {
                // Remove existing products
                $booking->products()->detach();

                // Attach new products with their current prices
                foreach ($request->products as $product) {
                    $productModel = Product::find($product['id']);
                    $booking->products()->attach($product['id'], [
                        'quantity' => $product['quantity'],
                        'price' => $productModel->price,
                    ]);
                }
            }

            return responder()->success($booking)->respond();
        } catch (\Exception $e) {
            return responder()->error($e->getMessage())->respond();
        }
    }

    public function destroy(string $id)
    {
        try {
            $booking = $this->bookingRepository->find($id);

            if (!$booking) {
                return responder()->error('Booking not found')->respond(Response::HTTP_NOT_FOUND);
            }

            $this->bookingRepository->adminDelete($booking);

            return responder()->success([])->respond(Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Booking deletion failed:', [
                'booking_id' => $id,
                'error' => $e->getMessage()
            ]);

            return responder()->error('Failed to delete booking: ' . $e->getMessage())
                ->respond(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function checkout(Request $request, string $id)
    {
        try {
            // Validate checkout request
            $request->validate([
                'actual_checkout_datetime' => 'nullable|date',
                'notes' => 'nullable|string',
                'early_checkout_reason' => 'nullable|string',
                'checkout_discount_amount' => 'nullable|numeric|min:0',
                'checkout_discount_reason' => 'nullable|string|max:500',
                'final_amount' => 'nullable|numeric|min:0',
                'deposit_refund_amount' => 'nullable|numeric|min:0',
                'payment_status' => 'nullable|string|in:refund,equal,remaining,normal'
            ]);

            $booking = $this->bookingRepository->find($id);
            if (!$booking) {
                return responder()->error('Booking not found')->respond(Response::HTTP_NOT_FOUND);
            }

            $checkoutData = $request->only([
                'actual_checkout_datetime',
                'notes',
                'early_checkout_reason',
                'checkout_discount_amount',
                'checkout_discount_reason',
                'final_amount',
                'deposit_refund_amount',
                'payment_status'
            ]);

            // Handle early checkout adjustments
            if ($request->has('early_checkout_reason')) {
                $checkoutData['status'] = 'checked_out';
                $checkoutData['checkout_notes'] = $request->early_checkout_reason;

                // If there's a deposit refund, add it to the notes
                if ($request->has('deposit_refund_amount') && $request->deposit_refund_amount > 0) {
                    $checkoutData['checkout_notes'] .= sprintf(
                        ' - تم استرداد %s جنيه من العربون نظراً للمغادرة المبكرة',
                        number_format($request->deposit_refund_amount, 2)
                    );
                }

                // Update final amount based on early checkout
                if ($request->has('final_amount')) {
                    $checkoutData['total_amount'] = $request->final_amount;
                }
            } else {
                $checkoutData['status'] = 'checked_out';
            }

            // Update booking with checkout data
            $this->bookingRepository->update($id, $checkoutData);

            // Get updated booking
            $booking = $this->bookingRepository->find($id);

            return responder()->success([
                'message' => 'Checkout completed successfully',
                'booking' => $booking
            ])->respond();

        } catch (\Exception $e) {
            return responder()->error($e->getMessage())->respond(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function active()
    {
        $data = $this->bookingRepository->getActiveBookings();

        return responder()->success($data, AbstractBookingTransformer::class)->respond(Response::HTTP_OK);
    }

    public function byVisitor(string $visitorId)
    {
        $data = $this->bookingRepository->getBookingsByVisitor($visitorId);

        return responder()->success($data, AbstractBookingTransformer::class)->respond(Response::HTTP_OK);
    }

    public function byApartment(string $apartmentId)
    {
        $data = $this->bookingRepository->getBookingsByApartment($apartmentId);

        return responder()->success($data, AbstractBookingTransformer::class)->respond(Response::HTTP_OK);
    }

    public function byDateRange(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $data = $this->bookingRepository->getBookingsByDateRange(
            $request->start_date,
            $request->end_date
        );

        return responder()->success($data, AbstractBookingTransformer::class)->respond(Response::HTTP_OK);
    }

    /**
     * Get booking products for a specific booking
     */
    public function getBookingProducts(string $id)
    {
        try {
            $booking = $this->bookingRepository->find($id);

            if (!$booking) {
                return responder()->error('Booking not found')->respond(Response::HTTP_NOT_FOUND);
            }

            $products = $booking->bookingProducts()->with('product')->get();

            return responder()->success($products, BookingProductTransformer::class)->respond(Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Get booking products failed:', [
                'booking_id' => $id,
                'error' => $e->getMessage()
            ]);

            return responder()->error('Failed to get booking products')
                ->respond(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Add product to existing booking
     */
    public function addProduct(Request $request, string $id)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:999',
            'unit_price' => 'required|numeric|min:0|max:9999.99',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $booking = $this->bookingRepository->find($id);

            if (!$booking) {
                return responder()->error('Booking not found')->respond(Response::HTTP_NOT_FOUND);
            }

            // Create new booking product
            $bookingProduct = $booking->bookingProducts()->create([
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'unit_price' => $request->unit_price,
                'notes' => $request->notes,
            ]);

            // Recalculate booking total
            $booking->total_amount = $booking->calculateAmount();
            $booking->save();

            return responder()->success($bookingProduct, BookingProductTransformer::class)
                ->respond(Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Add product to booking failed:', [
                'booking_id' => $id,
                'error' => $e->getMessage()
            ]);

            return responder()->error('Failed to add product to booking')
                ->respond(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove product from booking
     */
    public function removeProduct(string $bookingId, string $productId)
    {
        try {
            $booking = $this->bookingRepository->find($bookingId);

            if (!$booking) {
                return responder()->error('Booking not found')->respond(Response::HTTP_NOT_FOUND);
            }

            $bookingProduct = $booking->bookingProducts()->where('product_id', $productId)->first();

            if (!$bookingProduct) {
                return responder()->error('Product not found in booking')->respond(Response::HTTP_NOT_FOUND);
            }

            $bookingProduct->delete();

            // Recalculate booking total
            $booking->total_amount = $booking->calculateAmount();
            $booking->save();

            return responder()->success([])->respond(Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Remove product from booking failed:', [
                'booking_id' => $bookingId,
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return responder()->error('Failed to remove product from booking')
                ->respond(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}