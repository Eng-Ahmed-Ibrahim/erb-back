<?php

namespace Modules\ActivitiesSubscriptions\UI\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\ActivitiesSubscriptions\Domain\Repositories\AcademyRepositoryInterface;
use Modules\ActivitiesSubscriptions\Domain\Repositories\SubscriptionRepositoryInterface;

class FinancialReportsController extends Controller
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private AcademyRepositoryInterface $academyRepository
    ) {
    }

    /**
     * Get financial dashboard statistics for activity subscriptions
     */
    public function getDashboardStats(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

            $stats = $this->calculatePeriodStats($startDate, $endDate);

            // Previous period for comparison
            $previousStart = Carbon::parse($startDate)->subMonth()->format('Y-m-d');
            $previousEnd = Carbon::parse($endDate)->subMonth()->format('Y-m-d');
            $previousStats = $this->calculatePeriodStats($previousStart, $previousEnd);

            // Calculate growth
            $growth = $stats['total_revenue'] > 0 && $previousStats['total_revenue'] > 0
                ? (($stats['total_revenue'] - $previousStats['total_revenue']) / $previousStats['total_revenue']) * 100
                : 0;

            $data = [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'stats' => $stats,
                'previous_period' => [
                    'start_date' => $previousStart,
                    'end_date' => $previousEnd,
                    'stats' => $previousStats,
                ],
                'growth' => [
                    'revenue_growth_percentage' => round($growth, 2),
                    'subscriptions_growth_percentage' => $stats['total_subscriptions'] > 0 && $previousStats['total_subscriptions'] > 0
                        ? round((($stats['total_subscriptions'] - $previousStats['total_subscriptions']) / $previousStats['total_subscriptions']) * 100, 2)
                        : 0,
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Activity Subscriptions Dashboard Stats Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard stats',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get revenue analytics for activity subscriptions
     */
    public function getRevenueAnalytics(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
            $groupBy = $request->get('group_by', 'daily'); // daily, weekly, monthly, yearly

            $revenueData = match ($groupBy) {
                'daily' => $this->getDailyRevenue($startDate, $endDate),
                'weekly' => $this->getWeeklyRevenue($startDate, $endDate),
                'monthly' => $this->getMonthlyRevenue($startDate, $endDate),
                'yearly' => $this->getYearlyRevenue($startDate, $endDate),
                default => $this->getDailyRevenue($startDate, $endDate),
            };

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ],
                    'group_by' => $groupBy,
                    'revenue_data' => $revenueData,
                    'summary' => [
                        'total_revenue' => collect($revenueData)->sum('revenue'),
                        'average_revenue' => collect($revenueData)->avg('revenue'),
                        'total_subscriptions' => collect($revenueData)->sum('subscriptions_count'),
                    ],
                ],
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Activity Subscriptions Revenue Analytics Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch revenue analytics',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get subscriptions financial data with pricing information
     */
    public function getSubscriptionsFinancials(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
            $academyId = $request->get('academy_id');
            $subscriberType = $request->get('subscriber_type'); // infantry, civilian, other

            $query = DB::table('subscriptions')
                ->join('subscribers', 'subscriptions.subscriber_id', '=', 'subscribers.id')
                ->join('offers', 'subscriptions.offer_id', '=', 'offers.id')
                ->join('academies', 'subscriptions.academy_id', '=', 'academies.id')
                ->leftJoin('users as created_by', 'subscriptions.created_by', '=', 'created_by.id')
                ->whereBetween('subscriptions.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->select([
                    'subscriptions.id',
                    'subscriptions.created_at',
                    'subscriptions.status',
                    'subscriptions.start_date',
                    'subscriptions.end_date',
                    'subscribers.full_name as subscriber_name',
                    'subscribers.type as subscriber_type',
                    'subscribers.phone as subscriber_phone',
                    'offers.name as offer_name',
                    'offers.price_infantry',
                    'offers.price_civilian',
                    'offers.price_other',
                    'academies.id as academy_id',
                    'academies.name as academy_name',
                    'academies.revenue_share_infantry',
                    'academies.revenue_share_academy',
                    'created_by.name as created_by_name',
                    DB::raw("CASE 
                        WHEN subscribers.type = 'infantry' THEN offers.price_infantry
                        WHEN subscribers.type = 'civilian' THEN offers.price_civilian
                        ELSE offers.price_other
                    END as subscription_price"),
                    DB::raw("CASE 
                        WHEN subscribers.type = 'infantry' THEN (offers.price_infantry * academies.revenue_share_infantry / 100)
                        WHEN subscribers.type = 'civilian' THEN (offers.price_civilian * academies.revenue_share_infantry / 100)
                        ELSE (offers.price_other * academies.revenue_share_infantry / 100)
                    END as infantry_revenue_share"),
                    DB::raw("CASE 
                        WHEN subscribers.type = 'infantry' THEN (offers.price_infantry * academies.revenue_share_academy / 100)
                        WHEN subscribers.type = 'civilian' THEN (offers.price_civilian * academies.revenue_share_academy / 100)
                        ELSE (offers.price_other * academies.revenue_share_academy / 100)
                    END as academy_revenue_share"),
                ]);

            if ($academyId) {
                $query->where('subscriptions.academy_id', $academyId);
            }

            if ($subscriberType) {
                $query->where('subscribers.type', $subscriberType);
            }

            $subscriptions = $query->orderBy('subscriptions.created_at', 'desc')->get();

            $nonCancelledSubscriptions = $subscriptions->where('status', '!=', 'cancelled');

            $summary = [
                'total_subscriptions' => $nonCancelledSubscriptions->count(),
                'total_revenue' => $nonCancelledSubscriptions->sum('subscription_price'),
                'total_infantry_revenue_share' => $nonCancelledSubscriptions->sum('infantry_revenue_share'),
                'total_academy_revenue_share' => $nonCancelledSubscriptions->sum('academy_revenue_share'),
                'active_subscriptions' => $subscriptions->where('status', 'active')->count(),
                'expired_subscriptions' => $subscriptions->where('status', 'expired')->count(),
                'cancelled_subscriptions' => $subscriptions->where('status', 'cancelled')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ],
                    'filters' => [
                        'academy_id' => $academyId,
                        'subscriber_type' => $subscriberType,
                    ],
                    'subscriptions' => $subscriptions,
                    'summary' => $summary,
                ],
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Activity Subscriptions Financials Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subscriptions financials',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get revenue by academy
     */
    public function getRevenueByAcademy(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

            $revenueByAcademy = DB::table('subscriptions')
                ->join('subscribers', 'subscriptions.subscriber_id', '=', 'subscribers.id')
                ->join('offers', 'subscriptions.offer_id', '=', 'offers.id')
                ->join('academies', 'subscriptions.academy_id', '=', 'academies.id')
                ->whereBetween('subscriptions.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->where('subscriptions.status', '!=', 'cancelled')
                ->select([
                    'academies.id',
                    'academies.name as academy_name',
                    DB::raw("SUM(CASE 
                        WHEN subscribers.type = 'infantry' THEN offers.price_infantry
                        WHEN subscribers.type = 'civilian' THEN offers.price_civilian
                        ELSE offers.price_other
                    END) as total_revenue"),
                    DB::raw("SUM(CASE 
                        WHEN subscribers.type = 'infantry' THEN (offers.price_infantry * academies.revenue_share_infantry / 100)
                        WHEN subscribers.type = 'civilian' THEN (offers.price_civilian * academies.revenue_share_infantry / 100)
                        ELSE (offers.price_other * academies.revenue_share_infantry / 100)
                    END) as infantry_revenue_share"),
                    DB::raw("SUM(CASE 
                        WHEN subscribers.type = 'infantry' THEN (offers.price_infantry * academies.revenue_share_academy / 100)
                        WHEN subscribers.type = 'civilian' THEN (offers.price_civilian * academies.revenue_share_academy / 100)
                        ELSE (offers.price_other * academies.revenue_share_academy / 100)
                    END) as academy_revenue_share"),
                    DB::raw('COUNT(subscriptions.id) as subscriptions_count'),
                    DB::raw("COUNT(CASE WHEN subscriptions.status = 'active' THEN 1 END) as active_subscriptions"),
                ])
                ->groupBy('academies.id', 'academies.name')
                ->orderBy('total_revenue', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ],
                    'revenue_by_academy' => $revenueByAcademy,
                    'summary' => [
                        'total_revenue' => $revenueByAcademy->sum('total_revenue'),
                        'total_infantry_revenue_share' => $revenueByAcademy->sum('infantry_revenue_share'),
                        'total_academy_revenue_share' => $revenueByAcademy->sum('academy_revenue_share'),
                        'total_subscriptions' => $revenueByAcademy->sum('subscriptions_count'),
                    ],
                ],
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Activity Subscriptions Revenue By Academy Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch revenue by academy',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get revenue by subscriber type
     */
    public function getRevenueBySubscriberType(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

            $revenueByType = DB::table('subscriptions')
                ->join('subscribers', 'subscriptions.subscriber_id', '=', 'subscribers.id')
                ->join('offers', 'subscriptions.offer_id', '=', 'offers.id')
                ->whereBetween('subscriptions.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->select([
                    'subscribers.type as subscriber_type',
                    DB::raw("SUM(CASE 
                        WHEN subscribers.type = 'infantry' THEN offers.price_infantry
                        WHEN subscribers.type = 'civilian' THEN offers.price_civilian
                        ELSE offers.price_other
                    END) as total_revenue"),
                    DB::raw('COUNT(subscriptions.id) as subscriptions_count'),
                    DB::raw("COUNT(CASE WHEN subscriptions.status = 'active' THEN 1 END) as active_subscriptions"),
                    DB::raw('AVG(CASE 
                        WHEN subscribers.type = "infantry" THEN offers.price_infantry
                        WHEN subscribers.type = "civilian" THEN offers.price_civilian
                        ELSE offers.price_other
                    END) as average_subscription_price'),
                ])
                ->groupBy('subscribers.type')
                ->orderBy('total_revenue', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ],
                    'revenue_by_subscriber_type' => $revenueByType,
                    'summary' => [
                        'total_revenue' => $revenueByType->sum('total_revenue'),
                        'total_subscriptions' => $revenueByType->sum('subscriptions_count'),
                    ],
                ],
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Activity Subscriptions Revenue By Subscriber Type Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch revenue by subscriber type',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Generate comprehensive financial report
     */
    public function generateFinancialReport(Request $request, string $reportType): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

            $report = match ($reportType) {
                'summary' => $this->generateSummaryReport($startDate, $endDate),
                'subscriptions' => $this->generateSubscriptionsReport($startDate, $endDate),
                'revenue' => $this->generateRevenueReport($startDate, $endDate),
                'academies' => $this->generateAcademiesReport($startDate, $endDate),
                default => throw new \InvalidArgumentException('Invalid report type: ' . $reportType),
            };

            return response()->json([
                'success' => true,
                'data' => $report,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Activity Subscriptions Financial Report Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate financial report',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Private helper methods

    private function calculatePeriodStats(string $startDate, string $endDate): array
    {
        $stats = DB::table('subscriptions')
            ->join('subscribers', 'subscriptions.subscriber_id', '=', 'subscribers.id')
            ->join('offers', 'subscriptions.offer_id', '=', 'offers.id')
            ->whereBetween('subscriptions.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('subscriptions.status', '!=', 'cancelled')
            ->selectRaw('
                COUNT(*) as total_subscriptions,
                SUM(CASE 
                    WHEN subscribers.type = "infantry" THEN offers.price_infantry
                    WHEN subscribers.type = "civilian" THEN offers.price_civilian
                    ELSE offers.price_other
                END) as total_revenue,
                AVG(CASE 
                    WHEN subscribers.type = "infantry" THEN offers.price_infantry
                    WHEN subscribers.type = "civilian" THEN offers.price_civilian
                    ELSE offers.price_other
                END) as average_subscription_price,
                COUNT(CASE WHEN subscriptions.status = "active" THEN 1 END) as active_subscriptions,
                COUNT(CASE WHEN subscriptions.status = "expired" THEN 1 END) as expired_subscriptions
            ')
            ->first();

        $cancelledCount = DB::table('subscriptions')
            ->whereBetween('subscriptions.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('subscriptions.status', '=', 'cancelled')
            ->count();

        return [
            'total_subscriptions' => (int) ($stats->total_subscriptions ?? 0),
            'total_revenue' => (float) ($stats->total_revenue ?? 0),
            'average_subscription_price' => round((float) ($stats->average_subscription_price ?? 0), 2),
            'active_subscriptions' => (int) ($stats->active_subscriptions ?? 0),
            'expired_subscriptions' => (int) ($stats->expired_subscriptions ?? 0),
            'cancelled_subscriptions' => (int) $cancelledCount,
        ];
    }

    private function getDailyRevenue(string $startDate, string $endDate): array
    {
        return DB::table('subscriptions')
            ->join('subscribers', 'subscriptions.subscriber_id', '=', 'subscribers.id')
            ->join('offers', 'subscriptions.offer_id', '=', 'offers.id')
            ->whereBetween('subscriptions.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('
                DATE(subscriptions.created_at) as date,
                SUM(CASE 
                    WHEN subscribers.type = "infantry" THEN offers.price_infantry
                    WHEN subscribers.type = "civilian" THEN offers.price_civilian
                    ELSE offers.price_other
                END) as revenue,
                COUNT(*) as subscriptions_count
            ')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get()
            ->toArray();
    }

    private function getWeeklyRevenue(string $startDate, string $endDate): array
    {
        return DB::table('subscriptions')
            ->join('subscribers', 'subscriptions.subscriber_id', '=', 'subscribers.id')
            ->join('offers', 'subscriptions.offer_id', '=', 'offers.id')
            ->whereBetween('subscriptions.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('
                YEARWEEK(subscriptions.created_at) as week,
                SUM(CASE 
                    WHEN subscribers.type = "infantry" THEN offers.price_infantry
                    WHEN subscribers.type = "civilian" THEN offers.price_civilian
                    ELSE offers.price_other
                END) as revenue,
                COUNT(*) as subscriptions_count
            ')
            ->groupBy('week')
            ->orderBy('week', 'desc')
            ->get()
            ->toArray();
    }

    private function getMonthlyRevenue(string $startDate, string $endDate): array
    {
        return DB::table('subscriptions')
            ->join('subscribers', 'subscriptions.subscriber_id', '=', 'subscribers.id')
            ->join('offers', 'subscriptions.offer_id', '=', 'offers.id')
            ->whereBetween('subscriptions.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('
                YEAR(subscriptions.created_at) as year,
                MONTH(subscriptions.created_at) as month,
                SUM(CASE 
                    WHEN subscribers.type = "infantry" THEN offers.price_infantry
                    WHEN subscribers.type = "civilian" THEN offers.price_civilian
                    ELSE offers.price_other
                END) as revenue,
                COUNT(*) as subscriptions_count
            ')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->toArray();
    }

    private function getYearlyRevenue(string $startDate, string $endDate): array
    {
        return DB::table('subscriptions')
            ->join('subscribers', 'subscriptions.subscriber_id', '=', 'subscribers.id')
            ->join('offers', 'subscriptions.offer_id', '=', 'offers.id')
            ->whereBetween('subscriptions.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('
                YEAR(subscriptions.created_at) as year,
                SUM(CASE 
                    WHEN subscribers.type = "infantry" THEN offers.price_infantry
                    WHEN subscribers.type = "civilian" THEN offers.price_civilian
                    ELSE offers.price_other
                END) as revenue,
                COUNT(*) as subscriptions_count
            ')
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->get()
            ->toArray();
    }

    private function generateSummaryReport(string $startDate, string $endDate): array
    {
        $stats = $this->calculatePeriodStats($startDate, $endDate);
        $revenueByAcademy = DB::table('subscriptions')
            ->join('subscribers', 'subscriptions.subscriber_id', '=', 'subscribers.id')
            ->join('offers', 'subscriptions.offer_id', '=', 'offers.id')
            ->join('academies', 'subscriptions.academy_id', '=', 'academies.id')
            ->whereBetween('subscriptions.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->select([
                'academies.name as academy_name',
                DB::raw("SUM(CASE 
                    WHEN subscribers.type = 'infantry' THEN offers.price_infantry
                    WHEN subscribers.type = 'civilian' THEN offers.price_civilian
                    ELSE offers.price_other
                END) as total_revenue"),
                DB::raw('COUNT(*) as subscriptions_count'),
            ])
            ->groupBy('academies.name')
            ->get();

        return [
            'report_type' => 'summary',
            'period' => ['start_date' => $startDate, 'end_date' => $endDate],
            'stats' => $stats,
            'revenue_by_academy' => $revenueByAcademy,
        ];
    }

    private function generateSubscriptionsReport(string $startDate, string $endDate): array
    {
        $subscriptions = DB::table('subscriptions')
            ->join('subscribers', 'subscriptions.subscriber_id', '=', 'subscribers.id')
            ->join('offers', 'subscriptions.offer_id', '=', 'offers.id')
            ->join('academies', 'subscriptions.academy_id', '=', 'academies.id')
            ->whereBetween('subscriptions.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->select([
                'subscriptions.id',
                'subscriptions.created_at',
                'subscriptions.status',
                'subscribers.full_name as subscriber_name',
                'subscribers.type as subscriber_type',
                'offers.name as offer_name',
                'academies.name as academy_name',
                DB::raw("CASE 
                    WHEN subscribers.type = 'infantry' THEN offers.price_infantry
                    WHEN subscribers.type = 'civilian' THEN offers.price_civilian
                    ELSE offers.price_other
                END as subscription_price"),
            ])
            ->orderBy('subscriptions.created_at', 'desc')
            ->get();

        return [
            'report_type' => 'subscriptions',
            'period' => ['start_date' => $startDate, 'end_date' => $endDate],
            'data' => $subscriptions,
            'summary' => [
                'total_subscriptions' => $subscriptions->count(),
                'total_revenue' => $subscriptions->sum('subscription_price'),
            ],
        ];
    }

    private function generateRevenueReport(string $startDate, string $endDate): array
    {
        $dailyRevenue = $this->getDailyRevenue($startDate, $endDate);

        return [
            'report_type' => 'revenue',
            'period' => ['start_date' => $startDate, 'end_date' => $endDate],
            'daily_revenue' => $dailyRevenue,
            'summary' => [
                'total_revenue' => collect($dailyRevenue)->sum('revenue'),
                'average_daily_revenue' => collect($dailyRevenue)->avg('revenue'),
            ],
        ];
    }

    private function generateAcademiesReport(string $startDate, string $endDate): array
    {
        $revenueByAcademy = DB::table('subscriptions')
            ->join('subscribers', 'subscriptions.subscriber_id', '=', 'subscribers.id')
            ->join('offers', 'subscriptions.offer_id', '=', 'offers.id')
            ->join('academies', 'subscriptions.academy_id', '=', 'academies.id')
            ->whereBetween('subscriptions.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->select([
                'academies.id',
                'academies.name as academy_name',
                DB::raw("SUM(CASE 
                    WHEN subscribers.type = 'infantry' THEN offers.price_infantry
                    WHEN subscribers.type = 'civilian' THEN offers.price_civilian
                    ELSE offers.price_other
                END) as total_revenue"),
                DB::raw("SUM(CASE 
                    WHEN subscribers.type = 'infantry' THEN (offers.price_infantry * academies.revenue_share_infantry / 100)
                    WHEN subscribers.type = 'civilian' THEN (offers.price_civilian * academies.revenue_share_infantry / 100)
                    ELSE (offers.price_other * academies.revenue_share_infantry / 100)
                END) as infantry_revenue_share"),
                DB::raw("SUM(CASE 
                    WHEN subscribers.type = 'infantry' THEN (offers.price_infantry * academies.revenue_share_academy / 100)
                    WHEN subscribers.type = 'civilian' THEN (offers.price_civilian * academies.revenue_share_academy / 100)
                    ELSE (offers.price_other * academies.revenue_share_academy / 100)
                END) as academy_revenue_share"),
                DB::raw('COUNT(*) as subscriptions_count'),
            ])
            ->groupBy('academies.id', 'academies.name')
            ->orderBy('total_revenue', 'desc')
            ->get();

        return [
            'report_type' => 'academies',
            'period' => ['start_date' => $startDate, 'end_date' => $endDate],
            'data' => $revenueByAcademy,
            'summary' => [
                'total_revenue' => $revenueByAcademy->sum('total_revenue'),
                'total_infantry_revenue_share' => $revenueByAcademy->sum('infantry_revenue_share'),
                'total_academy_revenue_share' => $revenueByAcademy->sum('academy_revenue_share'),
            ],
        ];
    }
}
