<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\Apartment\ApartmentRepository;
use App\Repositories\Booking\BookingRepository;
use App\Repositories\Building\BuildingRepository;
use Illuminate\Http\Response;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Visitor;
use App\Http\Requests\Visitor\SearchVisitorRequest;

// use Flugg\Responder

class ReceptionStatsController extends Controller
{
    public function __construct(
        private BuildingRepository $buildingRepository,
        private ApartmentRepository $apartmentRepository,
        private BookingRepository $bookingRepository
    ) {
        $this->buildingRepository = $buildingRepository;
        $this->apartmentRepository = $apartmentRepository;
        $this->bookingRepository = $bookingRepository;
    }

    public function index()
    {
        $buildings = $this->buildingRepository->all();
        $apartments = $this->apartmentRepository->all();
        $bookings = $this->bookingRepository->all();

        $totalBuildings = $buildings->count();
        $totalApartments = $apartments->count();
        $availableApartments = $apartments->where('is_occupied', false)->where('is_active', true)->count();
        $occupiedApartments = $apartments->where('is_occupied', true)->count();

        $occupancyRate = $totalApartments > 0 ? round(($occupiedApartments / $totalApartments) * 100, 2) : 0;

        // Active guests count
        $activeGuests = $bookings->where('status', 'active')
            ->whereNotNull('visitor_id')
            ->count();

        // Today's checkouts and arrivals
        $today = now()->startOfDay();
        $todayCheckouts = $bookings->where('status', 'active')
            ->filter(function ($booking) use ($today) {
                $checkoutDate = $booking->checkout_datetime ?? $booking->check_out_date;
                return $checkoutDate && Carbon::parse($checkoutDate)->startOfDay()->eq($today);
            })
            ->count();

        $todayArrivals = $bookings->where('status', 'active')
            ->filter(function ($booking) use ($today) {
                return $booking->arrival_datetime && Carbon::parse($booking->arrival_datetime)->startOfDay()->eq($today);
            })
            ->count();

        // Group apartments by room type
        $apartmentsByType = $apartments->groupBy('room_type')->map(function ($typeApartments, $type) {
            return [
                'type' => $type,
                'total' => $typeApartments->count(),
                'occupied' => $typeApartments->where('is_occupied', true)->count(),
                'available' => $typeApartments->where('is_occupied', false)->where('is_active', true)->count(),
            ];
        });

        // Building occupancy stats
        $buildingStats = $buildings->map(function ($building) {
            return [
                'id' => $building->id,
                'name' => $building->name,
                'occupancy_stats' => $building->getOccupancyStats(),
            ];
        });

        $stats = [
            'total_buildings' => $totalBuildings,
            'total_apartments' => $totalApartments,
            'available_apartments' => $availableApartments,
            'occupied_apartments' => $occupiedApartments,
            'occupancy_rate' => $occupancyRate,
            'active_guests' => $activeGuests,
            'today_checkouts' => $todayCheckouts,
            'today_arrivals' => $todayArrivals,
            'apartments_by_type' => $apartmentsByType,
            'building_stats' => $buildingStats,
        ];

        // Add server time to response
        return responder()->success([
            'total_buildings' => $totalBuildings,
            'total_apartments' => $totalApartments,
            'available_apartments' => $availableApartments,
            'occupied_apartments' => $occupiedApartments,
            'occupancy_rate' => $occupancyRate,
            'active_guests' => $activeGuests,
            'today_checkouts' => $todayCheckouts,
            'server_time' => now()->toISOString()
        ])->respond(Response::HTTP_OK);
    }

    /**
     * Get current server time
     */
    public function getServerTime(Request $request)
    {
        try {

            $now = now();
            $serverTime = $now->toISOString();


            return response()->json([
                'success' => true,
                'data' => [
                    'server_time' => $serverTime,
                    'timezone' => config('app.timezone'),
                    'timestamp' => $now->timestamp
                ]
            ])
                ->header('Content-Type', 'application/json')
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Accept, Content-Type, X-Requested-With');
        } catch (\Exception $e) {
            \Log::error('Server time error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get server time',
                'error' => $e->getMessage()
            ], 500)
                ->header('Content-Type', 'application/json')
                ->header('Access-Control-Allow-Origin', '*');
        }
    }

    /**
     * Search visitors by ID number
     *
     * @param SearchVisitorRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchVisitors(SearchVisitorRequest $request)
    {
        $idNumber = $request->get('id_number');

        $visitors = Visitor::where('id_number', 'like', "%{$idNumber}%")
            ->select('id', 'id_number', 'name', 'phone', 'nationality')
            ->limit(10)
            ->get();

        return response()->json(['data' => $visitors]);
    }

}
