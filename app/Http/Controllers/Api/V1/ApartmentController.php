<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Apartment\StoreApartmentRequest;
use App\Repositories\Apartment\ApartmentRepository;
use App\Transformers\Apartment\AbstractApartmentTransformer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ApartmentController extends Controller
{
    public function __construct(
        private ApartmentRepository $apartmentRepository
    ) {
        $this->apartmentRepository = $apartmentRepository;
    }

    public function index(Request $request)
    {
        $buildingId = $request->input('building_id');
        $roomType = $request->input('room_type');
        $clientTypeId = $request->input('client_type_id');
        $occupancyStatus = $request->input('occupancy_status');
        $search = $request->input('search');
        $include = $request->input('include', ''); // For eager loading relationships

        $filters = [];
        if ($buildingId) {
            $filters['building_id'] = $buildingId;
        }
        if ($roomType) {
            $filters['room_type'] = $roomType;
        }
        if ($occupancyStatus) {
            if ($occupancyStatus === 'available') {
                $filters['is_occupied'] = false;
            } elseif ($occupancyStatus === 'occupied') {
                $filters['is_occupied'] = true;
            }
        }

        // Enhanced loading with visitor data
        $with = ['building', 'prices.clientType']; // Default relationships

        if (strpos($include, 'current_booking') !== false || strpos($include, 'bookings') !== false) {
            $with[] = 'bookings';
            $with[] = 'bookings.visitor';
            $with[] = 'bookings.visitor.clientType';
        }

        // Apply filters and search
        if (!empty($filters)) {
            $data = $this->apartmentRepository->getInterceptedByAttributes($filters, 'is_occupied', 'asc');
        } else {
            $data = $this->apartmentRepository->getInterceptedByAttributes([], 'is_occupied', 'asc');
        }

        $data = $this->apartmentRepository->paginate($data);

        return responder()->success($data, AbstractApartmentTransformer::class)->respond(Response::HTTP_OK);
    }

    public function show(string $id)
    {
        $data = $this->apartmentRepository->find($id);
        $data->load(['building', 'prices.clientType']);

        return responder()->success($data, AbstractApartmentTransformer::class)->respond(Response::HTTP_OK);
    }

    public function store(StoreApartmentRequest $request)
    {
        $data = $this->apartmentRepository->adminCreate($request->validated());

        return responder()->success($data, AbstractApartmentTransformer::class)->respond(Response::HTTP_CREATED);
    }

    public function update(StoreApartmentRequest $request, string $id)
    {
        $model = $this->apartmentRepository->find($id);
        $data = $this->apartmentRepository->adminUpdate($model, $request->validated());

        return responder()->success($data, AbstractApartmentTransformer::class)->respond(Response::HTTP_OK);
    }

    public function destroy(string $id)
    {
        $model = $this->apartmentRepository->find($id);
        $this->apartmentRepository->adminDelete($model);

        return responder()->success([])->respond(Response::HTTP_OK);
    }

    public function toggleOccupancy(string $id)
    {
        $apartment = $this->apartmentRepository->toggleOccupancy($id);

        return responder()->success($apartment, AbstractApartmentTransformer::class)->respond(Response::HTTP_OK);
    }

    public function getAvailableByBuilding(string $buildingId)
    {
        $apartments = $this->apartmentRepository->getAvailableByBuilding($buildingId);

        return responder()->success($apartments, AbstractApartmentTransformer::class)->respond(Response::HTTP_OK);
    }

    public function getByRoomType(string $roomType)
    {
        $apartments = $this->apartmentRepository->getByRoomType($roomType);

        return responder()->success($apartments, AbstractApartmentTransformer::class)->respond(Response::HTTP_OK);
    }

    public function getAvailableForDateRange(Request $request)
    {
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $buildingId = $request->input('building_id');
        $roomType = $request->input('room_type');

        if (!$fromDate || !$toDate) {
            return responder()->error('from_date and to_date are required')->respond(Response::HTTP_BAD_REQUEST);
        }

        $apartments = $this->apartmentRepository->getAvailableForDateRange($fromDate, $toDate, $buildingId, $roomType);

        return responder()->success($apartments, AbstractApartmentTransformer::class)->respond(Response::HTTP_OK);
    }

    public function isAvailableForDateRange(Request $request, $id)
    {
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');
        $currentBookingId = $request->query('current_booking_id') ?? null;

        if (!$fromDate || !$toDate) {
            return response()->json([
                'available' => false,
                'error' => 'from_date and to_date are required.'
            ], 400);
        }


        $apartment = $this->apartmentRepository->find($id);
        if (!$apartment) {
            return response()->json([
                'available' => false,
                'error' => 'Apartment not found.'
            ], 404);
        }

        $isAvailable = $apartment->isAvailableForDateRange($fromDate, $toDate, $currentBookingId);
        
        if ($isAvailable) {
            return response()->json(['available' => true]);
        } else {
            // Optionally, return conflicting bookings
            $conflicts = $apartment->bookings()
                ->whereIn('status', [
                    \App\Models\Booking::STATUS_ACTIVE,
                    \App\Models\Booking::STATUS_CONFIRMED,
                    \App\Models\Booking::STATUS_PENDING
                ])
                ->where(function ($query) use ($fromDate, $toDate) {
                    $query->where(function ($q) use ($fromDate, $toDate) {
                        $q->whereBetween('arrival_datetime', [$fromDate, $toDate]);
                    })->orWhere(function ($q) use ($fromDate, $toDate) {
                        $q->whereBetween('checkout_datetime', [$fromDate, $toDate]);
                    })->orWhere(function ($q) use ($fromDate, $toDate) {
                        $q->where('arrival_datetime', '<=', $fromDate)
                            ->where('checkout_datetime', '>=', $toDate);
                    });
                })
                ->get();
            return response()->json([
                'available' => false,
                'conflicts' => $conflicts
            ], 200);
        }
    }
}