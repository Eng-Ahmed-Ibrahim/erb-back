<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Visitor;
use App\Transformers\Booking\AbstractBookingTransformer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with(['visitor.clientType', 'apartment.building', 'creator']);

        // Filter by status (default to pending)
        $status = $request->input('status', Booking::STATUS_PENDING);
        if ($status) {
            $query->where('status', $status);
        }

        // Date range filter
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('arrival_datetime', [
                $request->input('from_date'),
                $request->input('to_date')
            ]);
        }

        // Building filter
        if ($request->filled('building_id')) {
            $query->whereHas('apartment', function ($q) use ($request) {
                $q->where('building_id', $request->input('building_id'));
            });
        }

        // Apartment filter
        if ($request->filled('apartment_id')) {
            $query->where('apartment_id', $request->input('apartment_id'));
        }

        // Visitor name search
        if ($request->filled('visitor_name')) {
            $query->whereHas('visitor', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->input('visitor_name') . '%');
            });
        }

        $reservations = $query->orderBy('arrival_datetime', 'asc')->paginate(15);

        return responder()->success($reservations, AbstractBookingTransformer::class)->respond(Response::HTTP_OK);
    }

    public function show(string $id)
    {
        $reservation = Booking::with(['visitor.clientType', 'apartment.building', 'creator'])
            ->findOrFail($id);

        return responder()->success($reservation, AbstractBookingTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'apartment_id' => 'required|exists:apartments,id',
            'arrival_datetime' => 'required|date|after:now',
            'checkout_datetime' => 'required|date|after:arrival_datetime',
            'duration_days' => 'required|integer|min:1',
            'total_amount' => 'numeric|min:0',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
            'meals' => 'nullable|array',
            'products' => 'nullable|array',
            'visitor' => 'required|array',
            'visitor.name' => 'required|string|max:255',
            'visitor.client_type_id' => 'required|exists:client_types,id',
            'visitor.id_type' => 'required|string',
            'visitor.id_number' => 'required|string',
            'visitor.nationality' => 'required|string',
            'visitor.phone' => 'nullable|string',
            'visitor.emergency_contact' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Create visitor first
            $visitor = Visitor::create($validated['visitor']);

            // Create reservation (pending booking)
            $reservationData = $validated;
            $reservationData['visitor_id'] = $visitor->id;
            $reservationData['status'] = Booking::STATUS_PENDING;
            $reservationData['created_by'] = auth()->id();
            unset($reservationData['visitor']);

            $reservation = Booking::create($reservationData);

            // Load relationships
            $reservation->load(['visitor.clientType', 'apartment.building', 'creator']);

            DB::commit();

            return responder()->success($reservation, AbstractBookingTransformer::class)->respond(Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();
            return responder()->error('Failed to create reservation: ' . $e->getMessage())->respond(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, string $id)
    {
        $reservation = Booking::findOrFail($id);

        if (!$reservation->isPending()) {
            return responder()->error('Only pending reservations can be updated')->respond(Response::HTTP_BAD_REQUEST);
        }

        $validated = $request->validate([
            'arrival_datetime' => 'sometimes|date|after:now',
            'checkout_datetime' => 'sometimes|date|after:arrival_datetime',
            'duration_days' => 'sometimes|integer|min:1',
            'total_amount' => 'sometimes|numeric|min:0',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
            'meals' => 'nullable|array',
        ]);

        $reservation->update($validated);
        $reservation->load(['visitor.clientType', 'apartment.building', 'creator']);

        return responder()->success($reservation, AbstractBookingTransformer::class)->respond(Response::HTTP_OK);
    }

    public function destroy(string $id)
    {
        $reservation = Booking::findOrFail($id);

        if (!$reservation->isPending()) {
            return responder()->error('Only pending reservations can be deleted')->respond(Response::HTTP_BAD_REQUEST);
        }

        $reservation->delete();

        return responder()->success([])->respond(Response::HTTP_OK);
    }

    public function confirm(string $id)
    {
        $reservation = Booking::findOrFail($id);

        if (!$reservation->isPending()) {
            return responder()->error('Only pending reservations can be confirmed')->respond(Response::HTTP_BAD_REQUEST);
        }

        try {
            $reservation->confirm();
            $reservation->load(['visitor.clientType', 'apartment.building', 'creator']);

            return responder()->success($reservation, AbstractBookingTransformer::class)->respond(Response::HTTP_OK);

        } catch (\Exception $e) {
            return responder()->error('Failed to confirm reservation: ' . $e->getMessage())->respond(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function activate(string $id)
    {
        $reservation = Booking::findOrFail($id);

        if (!$reservation->isPending() && !$reservation->isConfirmed()) {
            return responder()->error('Only pending or confirmed reservations can be activated')->respond(Response::HTTP_BAD_REQUEST);
        }

        try {
            $reservation->activate();
            $reservation->load(['visitor.clientType', 'apartment.building', 'creator']);

            return responder()->success($reservation, AbstractBookingTransformer::class)->respond(Response::HTTP_OK);

        } catch (\Exception $e) {
            return responder()->error('Failed to activate reservation: ' . $e->getMessage())->respond(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function cancel(Request $request, string $id)
    {
        $reservation = Booking::findOrFail($id);

        if (!$reservation->isPending()) {
            return responder()->error('Only pending reservations can be cancelled')->respond(Response::HTTP_BAD_REQUEST);
        }

        $reason = $request->input('cancellation_reason', 'Cancelled by admin');

        try {
            $reservation->cancel($reason);
            $reservation->load(['visitor.clientType', 'apartment.building', 'creator']);

            return responder()->success($reservation, AbstractBookingTransformer::class)->respond(Response::HTTP_OK);

        } catch (\Exception $e) {
            return responder()->error('Failed to cancel reservation: ' . $e->getMessage())->respond(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}