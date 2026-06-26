<?php

namespace Modules\ActivitiesSubscriptions\UI\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Modules\ActivitiesSubscriptions\Application\Commands\CheckInCommand;
use Modules\ActivitiesSubscriptions\Application\Handlers\CheckInHandler;
use Modules\ActivitiesSubscriptions\Domain\Repositories\AttendanceRepositoryInterface;
use Modules\ActivitiesSubscriptions\Domain\Repositories\SubscriptionRepositoryInterface;

class CheckInController extends Controller
{
    public function __construct(
        private CheckInHandler $checkInHandler,
        private AttendanceRepositoryInterface $attendanceRepository,
        private SubscriptionRepositoryInterface $subscriptionRepository
    ) {}

    /**
     * Check in a subscriber using QR code.
     */
    public function checkIn(Request $request): JsonResponse
    {
        $request->validate([
            'qr_code' => 'required|string',
            'check_in_date' => 'nullable|date',
            'day_of_week' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday'
        ]);

        try {
            $checkInDate = $request->check_in_date ? Carbon::parse($request->check_in_date) : Carbon::now();
            
            $command = new CheckInCommand(
                qrCode: $request->qr_code,
                checkInDate: $checkInDate,
                dayOfWeek: $request->day_of_week
            );
            
            $attendance = $this->checkInHandler->handle($command);

            return response()->json([
                'success' => true,
                'data' => $attendance,
                'message' => 'Check-in successful'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get attendance history for a subscription.
     */
    public function getAttendanceHistory(int $subscriptionId): JsonResponse
    {
        $attendance = $this->attendanceRepository->findBySubscriptionId($subscriptionId);
        
        return response()->json([
            'success' => true,
            'data' => $attendance
        ]);
    }
 
    /**
     * Get attendance history by date range.
     */
    public function getAttendanceByDateRange(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'subscription_id' => 'nullable|integer|exists:subscriptions,id',
            'academy_id' => 'nullable|integer|exists:academies,id'
        ]);

        $attendance = $this->attendanceRepository->getAttendanceWithDetails(
            $request->start_date ? Carbon::parse($request->start_date) : null,
            $request->end_date ? Carbon::parse($request->end_date) : null,
            $request->subscription_id,
            $request->academy_id
        );

        return response()->json([
            'success' => true,
            'data' => $attendance
        ]);
    }

    /**
     * Get attendance statistics.
     */
    public function getAttendanceStats(Request $request): JsonResponse
    {
        $request->validate([
            'subscription_id' => 'nullable|integer|exists:subscriptions,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date'
        ]);

        $stats = $this->attendanceRepository->getAttendanceStats(
            $request->start_date ? Carbon::parse($request->start_date) : null,
            $request->end_date ? Carbon::parse($request->end_date) : null,
            $request->subscription_id
        );

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Verify subscription information without checking in.
     */
    public function verifySubscription(Request $request): JsonResponse
    {
        $request->validate([
            'scanned_data' => 'required|string'
        ]);

        try {
            // Find subscription by QR code
            $subscription = $this->subscriptionRepository->findByQrCode($request->scanned_data);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'الاشتراك غير موجود أو بيانات الباركود غير صحيحة'
                ], 404);
            }

            // Check if subscription is active
            if (!$subscription->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'الاشتراك غير نشط'
                ], 422);
            }

            // Get remaining classes/hours from the subscription
            $remainingClasses = $subscription->getRemainingClasses();
            $remainingHours = $subscription->getRemainingHours();

            // For now, we'll use the subscription data directly
            // In a real implementation, you might want to fetch related data separately
            return response()->json([
                'success' => true,
                'subscription' => [
                    'id' => $subscription->getId(),
                    'subscriber_name' => 'مشترك', // You might need to fetch this separately
                    'academy_name' => 'أكاديمية', // You might need to fetch this separately
                    'offer_name' => 'عرض', // You might need to fetch this separately
                ],
                'remaining_classes' => $remainingClasses,
                'remaining_hours' => $remainingHours,
                'message' => 'تم التحقق من بيانات الاشتراك بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في التحقق من الاشتراك: ' . $e->getMessage()
            ], 500);
        }
    }
}
