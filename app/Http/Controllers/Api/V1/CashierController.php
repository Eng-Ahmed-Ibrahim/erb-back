<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\Booking\BookingRepository;
use App\Repositories\Building\BuildingRepository;
use App\Repositories\Visitor\VisitorRepository;
use App\Transformers\Booking\AbstractBookingTransformer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CashierController extends Controller
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private BuildingRepository $buildingRepository,
        private VisitorRepository $visitorRepository
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->buildingRepository = $buildingRepository;
        $this->visitorRepository = $visitorRepository;
    }

    public function dashboard()
    {
        $summary = $this->bookingRepository->getDashboardSummary();
        $occupancyStats = $this->buildingRepository->getOccupancyStats();

        $data = [
            'summary' => $summary,
            'buildings' => $occupancyStats,
        ];

        return responder()->success($data)->respond(Response::HTTP_OK);
    }

    public function filterBookings(Request $request)
    {
        $filters = $request->only([
            'visitor_type',
            'building_id',
            'room_type',
            'status',
            'arrival_date_from',
            'arrival_date_to',
            'meals'
        ]);

        $bookings = $this->bookingRepository->filterBookings($filters);

        return responder()->success($bookings, AbstractBookingTransformer::class)->respond(Response::HTTP_OK);
    }

    public function todayArrivals()
    {
        $today = now()->format('Y-m-d');
        $bookings = $this->bookingRepository->getBookingsByDateRange($today, $today);

        return responder()->success($bookings, AbstractBookingTransformer::class)->respond(Response::HTTP_OK);
    }

    public function todayCheckouts()
    {
        $bookings = $this->bookingRepository->getByAttributes([
            'checkout_datetime' => now()->format('Y-m-d') . ',%'
        ], 'checkout_datetime', 'desc');

        return responder()->success($bookings, AbstractBookingTransformer::class)->respond(Response::HTTP_OK);
    }

    public function reports(Request $request)
    {
        $type = $request->input('type', 'daily');
        $startDate = $request->input('start_date', now()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        switch ($type) {
            case 'weekly':
                $startDate = now()->startOfWeek()->format('Y-m-d');
                $endDate = now()->endOfWeek()->format('Y-m-d');
                break;
            case 'monthly':
                $startDate = now()->startOfMonth()->format('Y-m-d');
                $endDate = now()->endOfMonth()->format('Y-m-d');
                break;
        }

        $bookings = $this->bookingRepository->getBookingsByDateRange($startDate, $endDate);
        $summary = $this->bookingRepository->getDashboardSummary();

        $data = [
            'type' => $type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'bookings' => $bookings,
            'summary' => $summary,
        ];

        return responder()->success($data)->respond(Response::HTTP_OK);
    }
}