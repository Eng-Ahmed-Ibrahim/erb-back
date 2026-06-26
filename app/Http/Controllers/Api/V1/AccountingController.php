<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\Booking\BookingRepository;
use App\Repositories\PaymentMethod\PaymentMethodRepository;
use App\Repositories\User\UserRepository;
use App\Transformers\Booking\BookingTransformer;
use App\Transformers\User\UserTransformer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountingController extends Controller
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private UserRepository $userRepository,
        private PaymentMethodRepository $paymentMethodRepository
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->userRepository = $userRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    /**
     * Get comprehensive dashboard statistics
     */
    public function getDashboardStats(Request $request)
    {
        try {
            $user = Auth::user();
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth());

            // Check if user has permission to view all data or only their own
            $canViewAll = $user && ($user->can('view all accounting data') || $user->hasRole('admin'));

            // Current period stats
            $currentStats = $this->calculatePeriodStats($startDate, $endDate, $canViewAll ? null : $user->id);

            // Previous period for comparison
            $previousStart = Carbon::parse($startDate)->subMonth();
            $previousEnd = Carbon::parse($endDate)->subMonth();
            $previousStats = $this->calculatePeriodStats($previousStart, $previousEnd, $canViewAll ? null : $user->id);

            // Calculate growth
            $growth = $currentStats['total_revenue'] > 0 && $previousStats['total_revenue'] > 0
                ? (($currentStats['total_revenue'] - $previousStats['total_revenue']) / $previousStats['total_revenue']) * 100
                : 0;

            $data = [
                'total_revenue' => $currentStats['total_revenue'],
                'total_bookings' => $currentStats['total_bookings'],
                'average_booking_value' => $currentStats['average_booking_value'],
                'monthly_growth' => round($growth, 2),
                'active_bookings' => $currentStats['active_bookings'],
                'completed_bookings' => $currentStats['completed_bookings'],
                'total_nights' => $currentStats['total_nights'],
                'occupancy_rate' => $currentStats['occupancy_rate'],
                'user_permissions' => [
                    'can_view_all' => $canViewAll,
                    'user_id' => $user->id,
                    'user_name' => $user->name
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Dashboard stats error:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard stats'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get bookings with financial information
     */
    public function getBookingFinancials(Request $request)
    {
        try {
            $user = Auth::user();
            $canViewAll = $user && ($user->can('view all accounting data') || $user->hasRole('admin'));

            $query = DB::table('bookings')
                ->join('visitors', 'bookings.visitor_id', '=', 'visitors.id')
                ->join('apartments', 'bookings.apartment_id', '=', 'apartments.id')
                ->join('buildings', 'apartments.building_id', '=', 'buildings.id')
                ->leftJoin('users as created_by', 'bookings.created_by', '=', 'created_by.id')
                ->leftJoin('payment_methods', 'bookings.payment_method_id', '=', 'payment_methods.id')
                ->select([
                    'bookings.id',
                    'bookings.total_amount',
                    'bookings.status',
                    'bookings.arrival_datetime',
                    'bookings.checkout_datetime',
                    'bookings.created_at',
                    'visitors.name as visitor_name',
                    'visitors.phone as visitor_phone',
                    'apartments.apartment_number',
                    'buildings.name as building_name',
                    'created_by.name as created_by_name',
                    'created_by.id as created_by_id',
                    'payment_methods.name as payment_method_name'
                ])
                ->whereNotNull('bookings.total_amount')
                ->orderBy('bookings.created_at', 'desc');

            // Apply permission-based filtering
            if (!$canViewAll) {
                $query->where('bookings.created_by', $user->id);
            }

            // Apply date range filter
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('bookings.created_at', [
                    $request->get('start_date'),
                    $request->get('end_date')
                ]);
            }

            // Apply other filters only if user can view all or if filtering by their own data
            if ($request->has('staff_id')) {
                if ($canViewAll || $request->get('staff_id') == $user->id) {
                    $query->where('bookings.created_by', $request->get('staff_id'));
                }
            }

            if ($request->has('payment_method_id')) {
                $query->where('bookings.payment_method_id', $request->get('payment_method_id'));
            }

            if ($request->has('status')) {
                $query->where('bookings.status', $request->get('status'));
            }

            if ($request->has('building_id')) {
                $query->where('buildings.id', $request->get('building_id'));
            }

            $bookings = $query->paginate($request->get('per_page', 15));

            // Add permission info to response
            $response = $bookings->toArray();
            $response['user_permissions'] = [
                'can_view_all' => $canViewAll,
                'user_id' => $user->id,
                'user_name' => $user->name
            ];

            return response()->json([
                'success' => true,
                'data' => $response
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Booking financials error:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch booking financials'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get bookings by date range
     */
    public function getBookingsByDateRange(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $data = $this->bookingRepository->getBookingsByDateRange(
                $request->get('start_date'),
                $request->get('end_date')
            );

            return responder()->success($data, BookingTransformer::class)->respond(Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Bookings by date range error:', ['error' => $e->getMessage()]);
            return responder()
                ->error('Failed to fetch bookings by date range')
                ->respond(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get bookings by staff member
     */
    public function getBookingsByStaff(Request $request, string $staffId)
    {
        try {
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $query = DB::table('bookings')
                ->join('visitors', 'bookings.visitor_id', '=', 'visitors.id')
                ->join('apartments', 'bookings.apartment_id', '=', 'apartments.id')
                ->join('buildings', 'apartments.building_id', '=', 'buildings.id')
                ->leftJoin('users as created_by', 'bookings.created_by', '=', 'created_by.id')
                ->leftJoin('payment_methods', 'bookings.payment_method_id', '=', 'payment_methods.id')
                ->where('bookings.created_by', $staffId)
                ->select([
                    'bookings.id',
                    'bookings.total_amount',
                    'bookings.status',
                    'bookings.arrival_datetime',
                    'bookings.checkout_datetime',
                    'bookings.created_at',
                    'visitors.name as visitor_name',
                    'visitors.phone as visitor_phone',
                    'apartments.apartment_number',
                    'buildings.name as building_name',
                    'created_by.name as created_by_name',
                    'payment_methods.name as payment_method_name'
                ])
                ->orderBy('bookings.created_at', 'desc');

            if ($startDate && $endDate) {
                $query->whereBetween('bookings.created_at', [$startDate, $endDate]);
            }

            $bookings = $query->paginate($request->get('per_page', 15));

            return responder()->success($bookings)->respond(Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Bookings by staff error:', ['error' => $e->getMessage()]);
            return responder()
                ->error('Failed to fetch bookings by staff')
                ->respond(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get revenue analytics
     */
    public function getRevenueAnalytics(Request $request)
    {
        try {
            $period = $request->get('period', 'monthly');
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth());

            $data = [];

            switch ($period) {
                case 'daily':
                    $data = $this->getDailyRevenue($startDate, $endDate);
                    break;
                case 'weekly':
                    $data = $this->getWeeklyRevenue($startDate, $endDate);
                    break;
                case 'monthly':
                    $data = $this->getMonthlyRevenue($startDate, $endDate);
                    break;
                case 'yearly':
                    $data = $this->getYearlyRevenue($startDate, $endDate);
                    break;
            }

            return response()->json(['success' => true, 'data' => $data], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Revenue analytics error:', ['error' => $e->getMessage()]);
            return responder()
                ->error('Failed to fetch revenue analytics')
                ->respond(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get user booking reports - for each user, show booking count in selected time period
     */
    public function getUserBookingReports(Request $request)
    {
        try {
            $user = Auth::user();
            $canViewAll = $user && ($user->can('view all accounting data') || $user->hasRole('admin'));
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth());

            $query = DB::table('users')
                ->leftJoin('bookings', 'users.id', '=', 'bookings.created_by')
                ->select([
                    'users.id',
                    'users.name',
                    DB::raw('COUNT(bookings.id) as bookings_count'),
                    DB::raw('COALESCE(SUM(bookings.total_amount), 0) as total_revenue'),
                    DB::raw('COALESCE(AVG(bookings.total_amount), 0) as average_booking_value')
                ])
                ->whereBetween('bookings.created_at', [$startDate, $endDate])
                ->groupBy('users.id', 'users.name')
                ->orderBy('bookings_count', 'desc');

            if (!$canViewAll) {
                $query->where('users.id', $user->id);
            }

            if ($request->has('user_name') && $canViewAll) {
                $query->where('users.name', 'LIKE', '%' . $request->get('user_name') . '%');
            }

            // Apply user ID filter if provided
            if ($request->has('user_id') && $canViewAll) {
                $query->where('users.id', $request->get('user_id'));
            }

            $userReports = $query->get();

            $response = [
                'data' => $userReports,
                'user_permissions' => [
                    'can_view_all' => $canViewAll,
                    'user_id' => $user->id,
                    'user_name' => $user->name
                ],
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $response
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('User booking reports error:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user booking reports'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get staff performance analytics
     */
    public function getStaffPerformance(Request $request)
    {
        try {
            $user = Auth::user();
            $canViewAll = $user && ($user->can('view all accounting data') || $user->hasRole('admin'));
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth());

            $query = DB::table('users')
                ->leftJoin('bookings', 'users.id', '=', 'bookings.created_by')
                ->select([
                    'users.id',
                    'users.name',
                    DB::raw('COUNT(bookings.id) as bookings_count'),
                    DB::raw('COALESCE(SUM(bookings.total_amount), 0) as total_revenue'),
                    DB::raw('COALESCE(AVG(bookings.total_amount), 0) as average_booking_value')
                ])
                ->whereBetween('bookings.created_at', [$startDate, $endDate])
                ->groupBy('users.id', 'users.name')
                ->having('bookings_count', '>', 0)
                ->orderBy('total_revenue', 'desc');

            // If user can't view all, only show their own performance
            if (!$canViewAll) {
                $query->where('users.id', $user->id);
            }

            $staffPerformance = $query->get();

            $response = [
                'data' => $staffPerformance,
                'user_permissions' => [
                    'can_view_all' => $canViewAll,
                    'user_id' => $user->id,
                    'user_name' => $user->name
                ]
            ];

            return responder()->success($response)->respond(Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Staff performance error:', ['error' => $e->getMessage()]);
            return responder()
                ->error('Failed to fetch staff performance')
                ->respond(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Get payment method breakdown */
    // public function getPaymentMethodBreakdown(Request $request)
    // {
    //     try {
    //         $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
    //         $endDate = $request->get('end_date', Carbon::now()->endOfMonth());

    //         $paymentBreakdown = DB::table('bookings')
    //             ->leftJoin('payment_methods', 'bookings.payment_method_id', '=', 'payment_methods.id')
    //             ->select([
    //                 'payment_methods.name',
    //                 DB::raw('COUNT(bookings.id) as count'),
    //                 DB::raw('COALESCE(SUM(bookings.total_amount), 0) as total_amount')
    //             ])
    //             ->whereBetween('bookings.created_at', [$startDate, $endDate])
    //             ->whereNotNull('bookings.total_amount')
    //             ->groupBy('payment_methods.id', 'payment_methods.name')
    //             ->get();

    //         // Calculate percentages
    //         $totalAmount = $paymentBreakdown->sum('total_amount');
    //         $paymentBreakdown = $paymentBreakdown->map(function ($item) use ($totalAmount) {
    //             $item->percentage = $totalAmount > 0 ? round(($item->total_amount / $totalAmount) * 100, 2) : 0;
    //             return $item;
    //         });

    //         return responder()->success($paymentBreakdown)->respond(Response::HTTP_OK);
    //     } catch (\Exception $e) {
    //         Log::error('Payment method breakdown error:', ['error' => $e->getMessage()]);
    //         return responder()->error('Failed to fetch payment method breakdown')
    //             ->respond(Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    /**
     * Get booking status breakdown
     */
    public function getBookingStatusBreakdown(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth());

            $statusBreakdown = DB::table('bookings')
                ->select([
                    'status',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('COALESCE(SUM(total_amount), 0) as total_amount')
                ])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('status')
                ->get();

            // Calculate percentages
            $totalCount = $statusBreakdown->sum('count');
            $statusBreakdown = $statusBreakdown->map(function ($item) use ($totalCount) {
                $item->percentage = $totalCount > 0 ? round(($item->count / $totalCount) * 100, 2) : 0;
                return $item;
            });

            return responder()->success($statusBreakdown)->respond(Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Booking status breakdown error:', ['error' => $e->getMessage()]);
            return responder()
                ->error('Failed to fetch booking status breakdown')
                ->respond(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get average booking value
     */
    public function getAverageBookingValue(Request $request)
    {
        try {
            $period = $request->get('period', 'month');
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth());

            $averageValue = DB::table('bookings')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereNotNull('total_amount')
                ->avg('total_amount');

            return responder()->success([
                'average_booking_value' => round($averageValue ?: 0, 2),
                'period' => $period,
                'start_date' => $startDate,
                'end_date' => $endDate
            ])->respond(Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Average booking value error:', ['error' => $e->getMessage()]);
            return responder()
                ->error('Failed to fetch average booking value')
                ->respond(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get top performing staff
     */
    public function getTopPerformingStaff(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);
            $period = $request->get('period', 'month');

            $startDate = match ($period) {
                'week' => Carbon::now()->startOfWeek(),
                'month' => Carbon::now()->startOfMonth(),
                'year' => Carbon::now()->startOfYear(),
                default => Carbon::now()->startOfMonth()
            };

            $endDate = match ($period) {
                'week' => Carbon::now()->endOfWeek(),
                'month' => Carbon::now()->endOfMonth(),
                'year' => Carbon::now()->endOfYear(),
                default => Carbon::now()->endOfMonth()
            };

            $topStaff = DB::table('users')
                ->leftJoin('bookings', 'users.id', '=', 'bookings.created_by')
                ->select([
                    'users.id',
                    'users.name',
                    DB::raw('COUNT(bookings.id) as bookings_count'),
                    DB::raw('COALESCE(SUM(bookings.total_amount), 0) as total_revenue')
                ])
                ->whereBetween('bookings.created_at', [$startDate, $endDate])
                ->groupBy('users.id', 'users.name')
                ->having('bookings_count', '>', 0)
                ->orderBy('total_revenue', 'desc')
                ->limit($limit)
                ->get();

            return responder()->success($topStaff)->respond(Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Top performing staff error:', ['error' => $e->getMessage()]);
            return responder()
                ->error('Failed to fetch top performing staff')
                ->respond(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Generate financial report
     */
    public function generateFinancialReport(Request $request, string $reportType)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth());

            switch ($reportType) {
                case 'bookings':
                    return $this->generateBookingsReport($startDate, $endDate);
                case 'revenue':
                    return $this->generateRevenueReport($startDate, $endDate);
                case 'staff':
                    return $this->generateStaffReport($startDate, $endDate);
                default:
                    return responder()
                        ->error('Invalid report type')
                        ->respond(Response::HTTP_BAD_REQUEST);
            }
        } catch (\Exception $e) {
            Log::error('Financial report generation error:', ['error' => $e->getMessage()]);
            return responder()
                ->error('Failed to generate financial report')
                ->respond(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Private helper methods

    private function calculatePeriodStats($startDate, $endDate, $userId = null)
    {
        $query = DB::table('bookings')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('total_amount');

        if ($userId) {
            $query->where('created_by', $userId);
        }

        $stats = $query
            ->selectRaw('
                COUNT(*) as total_bookings,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as average_booking_value,
                COUNT(CASE WHEN status = "active" THEN 1 END) as active_bookings,
                COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_bookings
            ')
            ->first();

        // Calculate total nights
        $nightsQuery = DB::table('bookings')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('total_amount');

        if ($userId) {
            $nightsQuery->where('created_by', $userId);
        }

        $totalNights = $nightsQuery
            ->selectRaw('SUM(DATEDIFF(checkout_datetime, arrival_datetime)) as total_nights')
            ->value('total_nights') ?: 0;

        // Calculate occupancy rate (simplified)
        $totalRooms = DB::table('apartments')->count();
        $occupancyRate = $totalRooms > 0 ? ($stats->active_bookings / $totalRooms) * 100 : 0;

        return [
            'total_bookings' => $stats->total_bookings ?: 0,
            'total_revenue' => $stats->total_revenue ?: 0,
            'average_booking_value' => round($stats->average_booking_value ?: 0, 2),
            'active_bookings' => $stats->active_bookings ?: 0,
            'completed_bookings' => $stats->completed_bookings ?: 0,
            'total_nights' => $totalNights,
            'occupancy_rate' => round($occupancyRate, 2)
        ];
    }

    private function getDailyRevenue($startDate, $endDate)
    {
        return DB::table('bookings')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('total_amount')
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue, COUNT(*) as bookings')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();
    }

    private function getWeeklyRevenue($startDate, $endDate)
    {
        return DB::table('bookings')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('total_amount')
            ->selectRaw('YEARWEEK(created_at) as week, SUM(total_amount) as revenue, COUNT(*) as bookings')
            ->groupBy('week')
            ->orderBy('week', 'desc')
            ->get();
    }

    private function getMonthlyRevenue($startDate, $endDate)
    {
        return DB::table('bookings')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('total_amount')
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(total_amount) as revenue, COUNT(*) as bookings')
            ->groupBy('year', 'month')
            ->orderBy('month', 'desc')
            ->get();
    }

    private function getYearlyRevenue($startDate, $endDate)
    {
        return DB::table('bookings')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('total_amount')
            ->selectRaw('YEAR(created_at) as year, SUM(total_amount) as revenue, COUNT(*) as bookings')
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->get();
    }

    private function generateBookingsReport($startDate, $endDate)
    {
        // This would typically generate a CSV/Excel/PDF report
        // For now, returning JSON data
        $bookings = DB::table('bookings')
            ->join('visitors', 'bookings.visitor_id', '=', 'visitors.id')
            ->join('apartments', 'bookings.apartment_id', '=', 'apartments.id')
            ->join('buildings', 'apartments.building_id', '=', 'buildings.id')
            ->leftJoin('users as created_by', 'bookings.created_by', '=', 'created_by.id')
            ->leftJoin('payment_methods', 'bookings.payment_method_id', '=', 'payment_methods.id')
            ->whereBetween('bookings.created_at', [$startDate, $endDate])
            ->select([
                'bookings.id',
                'bookings.total_amount',
                'bookings.status',
                'bookings.arrival_datetime',
                'bookings.checkout_datetime',
                'bookings.created_at',
                'visitors.name as visitor_name',
                'apartments.apartment_number',
                'buildings.name as building_name',
                'created_by.name as created_by_name',
                'payment_methods.name as payment_method_name'
            ])
            ->get();

        return responder()->success([
            'report_type' => 'bookings',
            'period' => ['start' => $startDate, 'end' => $endDate],
            'data' => $bookings,
            'summary' => [
                'total_bookings' => $bookings->count(),
                'total_revenue' => $bookings->sum('total_amount')
            ]
        ])->respond(Response::HTTP_OK);
    }

    private function generateRevenueReport($startDate, $endDate)
    {
        $dailyRevenue = $this->getDailyRevenue($startDate, $endDate);
        $monthlyRevenue = $this->getMonthlyRevenue($startDate, $endDate);

        return responder()->success([
            'report_type' => 'revenue',
            'period' => ['start' => $startDate, 'end' => $endDate],
            'daily_revenue' => $dailyRevenue,
            'monthly_revenue' => $monthlyRevenue,
            'summary' => [
                'total_revenue' => $dailyRevenue->sum('revenue'),
                'average_daily_revenue' => $dailyRevenue->avg('revenue')
            ]
        ])->respond(Response::HTTP_OK);
    }

    private function generateStaffReport($startDate, $endDate)
    {
        $staffPerformance = DB::table('users')
            ->leftJoin('bookings', 'users.id', '=', 'bookings.created_by')
            ->whereBetween('bookings.created_at', [$startDate, $endDate])
            ->select([
                'users.id',
                'users.name',
                DB::raw('COUNT(bookings.id) as bookings_count'),
                DB::raw('COALESCE(SUM(bookings.total_amount), 0) as total_revenue'),
                DB::raw('COALESCE(AVG(bookings.total_amount), 0) as average_booking_value')
            ])
            ->groupBy('users.id', 'users.name')
            ->having('bookings_count', '>', 0)
            ->orderBy('total_revenue', 'desc')
            ->get();

        return responder()->success([
            'report_type' => 'staff',
            'period' => ['start' => $startDate, 'end' => $endDate],
            'data' => $staffPerformance,
            'summary' => [
                'total_staff' => $staffPerformance->count(),
                'total_revenue' => $staffPerformance->sum('total_revenue'),
                'total_bookings' => $staffPerformance->sum('bookings_count')
            ]
        ])->respond(Response::HTTP_OK);
    }
}
