<?php

namespace Modules\ActivitiesSubscriptions\UI\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\ActivitiesSubscriptions\Application\Commands\CreateSubscriptionCommand;
use Modules\ActivitiesSubscriptions\Application\DTOs\CreateSubscriptionDTO;
use Modules\ActivitiesSubscriptions\Application\Handlers\CreateSubscriptionHandler;
use Modules\ActivitiesSubscriptions\Domain\Entities\Attendance;
use Modules\ActivitiesSubscriptions\Domain\Repositories\AcademyRepositoryInterface;
use Modules\ActivitiesSubscriptions\Domain\Repositories\AttendanceRepositoryInterface;
use Modules\ActivitiesSubscriptions\Domain\Repositories\OfferRepositoryInterface;
use Modules\ActivitiesSubscriptions\Domain\Repositories\SubscriberRepositoryInterface;
use Modules\ActivitiesSubscriptions\Domain\Repositories\SubscriptionRepositoryInterface;
use Modules\ActivitiesSubscriptions\Domain\Services\QRCodeImageService;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private OfferRepositoryInterface $offerRepository,
        private SubscriberRepositoryInterface $subscriberRepository,
        private AcademyRepositoryInterface $academyRepository,
        private AttendanceRepositoryInterface $attendanceRepository,
        private CreateSubscriptionHandler $createSubscriptionHandler,
        private QRCodeImageService $qrCodeImageService
    ) {}

    /**
     * Display a listing of subscriptions.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['date_from', 'date_to', 'created_by', 'academy_id']);
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $result = $this->subscriptionRepository->findWithFilters($filters, $perPage, $page);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'pagination' => $result['pagination']
        ]);
    }

    /**
     * Store a newly created subscription.
     */
    public function store(Request $request): JsonResponse
    {
        // Validate basic subscription data
        $request->validate([
            'offer_id' => 'required|integer|exists:offers,id',
            'academy_id' => 'required|integer|exists:academies,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'chosen_days' => 'required|array',
            'chosen_days.*' => 'string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'status' => 'string|in:active,expired,cancelled',
            // Subscriber validation - either existing or new
            'subscriber_id' => 'nullable|integer|exists:subscribers,id',
            'subscriber_data' => 'nullable|array',
            'subscriber_data.full_name' => 'required_with:subscriber_data|string|max:255',
            'subscriber_data.type' => 'required_with:subscriber_data|string|in:infantry,civilian,other',
            'subscriber_data.phone' => 'nullable|string|max:20',
            'subscriber_data.national_id' => 'nullable|string|max:20',
            'subscriber_data.military_id' => 'nullable|string|max:20',
        ]);

        // Ensure either subscriber_id or subscriber_data is provided
        if (!$request->has('subscriber_id') && !$request->has('subscriber_data')) {
            return response()->json([
                'success' => false,
                'message' => 'Either subscriber_id or subscriber_data must be provided'
            ], 422);
        }

        try {
            // Get offer to determine initial values
            $offer = $this->offerRepository->findById($request->offer_id);
            if (!$offer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Offer not found'
                ], 404);
            }

            // Get academy_id from the request and validate it matches the offer's academy
            $academyId = $request->academy_id;

            // Validate that the academy_id matches the offer's academy
            if ($offer->getAcademyId() != $academyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Academy ID does not match the selected offer'
                ], 422);
            }

            // Handle subscriber - either existing or create new one
            $subscriberId = $request->subscriber_id;

            if (!$subscriberId && $request->has('subscriber_data')) {
                // Create new subscriber
                $subscriberData = $request->subscriber_data;

                // Validate that the appropriate ID is provided based on type
                if ($subscriberData['type'] === 'civilian' && empty($subscriberData['national_id'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'National ID is required for civilian subscribers'
                    ], 422);
                }

                if ($subscriberData['type'] === 'infantry' && empty($subscriberData['military_id'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Military ID is required for infantry subscribers'
                    ], 422);
                }

                // Create subscriber entity
                $subscriber = new \Modules\ActivitiesSubscriptions\Domain\Entities\Subscriber(
                    $subscriberData['full_name'],
                    $subscriberData['type'],
                    $subscriberData['national_id'] ?? null,
                    $subscriberData['military_id'] ?? null,
                    $subscriberData['phone'] ?? null
                );

                // Save subscriber
                $savedSubscriber = $this->subscriberRepository->save($subscriber);
                $subscriberId = $savedSubscriber->getId();
            }

            $dto = CreateSubscriptionDTO::fromArray([
                'subscriber_id' => $subscriberId,
                'offer_id' => $request->offer_id,
                'academy_id' => $academyId,
                'created_by' => auth()->user()->id ?? '01hy3km0ce0hv4w80hadt8sbt1', // Use authenticated user ULID or default admin ULID
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'chosen_days' => $request->chosen_days,
                'status' => $request->status ?? 'active',
                'initial_classes' => $offer->getNumClasses() ?? 0,
                'initial_hours' => $offer->getNumHours() ?? 0,
            ]);
            
            $command = new CreateSubscriptionCommand($dto);
            $subscription = $this->createSubscriptionHandler->handle($command);

            // Generate QR code image for the new subscription
            $subscriptionData = $this->transformSubscriptionToArray($subscription);
            $qrCodeInfo = $this->qrCodeImageService->generateQRCodeForSubscription(
                $subscription->getId(),
                $subscriptionData
            );

            $responseData = $this->transformSubscriptionToArray($subscription);
            $responseData['qr_code_image'] = $qrCodeInfo;

            return response()->json([
                'success' => true,
                'data' => $responseData,
                'message' => 'Subscription processed successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified subscription.
     */
    public function show(int $id): JsonResponse
    {
        $subscription = $this->subscriptionRepository->findById($id);
        
        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformSubscriptionToArray($subscription)
        ]);
    }

    /**
     * Update the specified subscription.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $subscription = $this->subscriptionRepository->findById($id);
        
        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found'
            ], 404);
        }

        $currentEndDate = $subscription->getDuration()->getEndDate()->format('Y-m-d');

        $request->validate([
            'status' => 'sometimes|string|in:active,expired,cancelled',
            'end_date' => 'sometimes|date|after_or_equal:' . $currentEndDate,
            'start_date' => 'sometimes|date',
        ]);

        try {
            if ($request->has('status')) {
                switch ($request->status) {
                    case 'cancelled':
                        $subscription->cancel();
                        break;
                    case 'expired':
                        $subscription->expire();
                        break;
                    case 'active':
                        // Reactivate if needed
                        break;
                }
            }

            if ($request->filled('end_date')) {
                $newEndDate = Carbon::parse($request->end_date);
                $subscription->extendEndDate($newEndDate);
            }
            if ($request->filled('start_date')) {
                $newStartDate = Carbon::parse($request->start_date);
                $subscription->updateStartDate($newStartDate);
            }
            $updatedSubscription = $this->subscriptionRepository->save($subscription);
            $responseData = $this->transformSubscriptionToArray($updatedSubscription);

            return response()->json([
                'success' => true,
                'data' => $responseData,
                'message' => 'Subscription updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified subscription.
     */
    public function destroy(int $id): JsonResponse
    {
        $subscription = $this->subscriptionRepository->findById($id);
        
        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found'
            ], 404);
        }

        $this->subscriptionRepository->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Subscription deleted successfully'
        ]);
    }

    /**
     * Get subscriptions by subscriber.
     */
    public function getBySubscriber(int $subscriberId): JsonResponse
    {
        $subscriptions = $this->subscriptionRepository->findBySubscriberId($subscriberId);
        
        return response()->json([
            'success' => true,
            'data' => $subscriptions
        ]);
    }

    /**
     * Get subscriptions by academy.
     */
    public function getByAcademy(int $academyId): JsonResponse
    {
        $subscriptions = $this->subscriptionRepository->findByAcademyId($academyId);
        
        return response()->json([
            'success' => true,
            'data' => $subscriptions
        ]);
    }

    /**
     * Generate QR code for subscription.
     */
    public function generateQR(int $id): JsonResponse
    {
        $subscription = $this->subscriptionRepository->findById($id);
        
        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found'
            ], 404);
        }

        // Get subscription data for QR code
        $subscriptionData = $this->transformSubscriptionToArray($subscription);

        // Generate QR code image
        $qrCodeInfo = $this->qrCodeImageService->generateQRCodeForSubscription(
            $subscription->getId(),
            $subscriptionData
        );

        // Debug logging
        \Log::info('QR Code Info Generated:', $qrCodeInfo);
        \Log::info('QR Code SVG Data Length:', ['length' => strlen($qrCodeInfo['svg_data'] ?? '')]);

        return response()->json([
            'success' => true,
            'data' => [
                'qr_code' => $subscription->getQrCode()->getValue(),
                'qr_code_image' => $qrCodeInfo,
                'subscription_id' => $subscription->getId(),
                'subscription_data' => $subscriptionData
            ],
            'message' => 'QR code generated successfully'
        ]);
    }

    /**
     * Get barcode SVG file for a subscription.
     */
    public function getBarcodeSVG(int $id): \Illuminate\Http\Response
    {
        $subscription = $this->subscriptionRepository->findById($id);

        if (!$subscription) {
            return response('Subscription not found', 404);
        }

        // Get the latest barcode file for this subscription
        $barcodeFiles = \Storage::files('qr-codes');
        $subscriptionFiles = array_filter($barcodeFiles, function ($file) use ($id) {
            return strpos($file, "subscription_{$id}_barcode_") === 0;
        });

        if (empty($subscriptionFiles)) {
            // Generate a new barcode if none exists
            $subscriptionData = $this->transformSubscriptionToArray($subscription);
            $qrCodeInfo = $this->qrCodeImageService->generateQRCodeForSubscription(
                $subscription->getId(),
                $subscriptionData
            );

            // Get the barcode filename from the generated info
            $barcodeInfo = $qrCodeInfo['barcode'] ?? null;
            if (!$barcodeInfo || !isset($barcodeInfo['filename'])) {
                return response('Barcode generation failed', 500);
            }

            $filePath = 'qr-codes/' . $barcodeInfo['filename'];
        } else {
            // Get the most recent file
            $filePath = end($subscriptionFiles);
        }

        if (!\Storage::exists($filePath)) {
            return response('Barcode file not found', 404);
        }

        $svgContent = \Storage::get($filePath);

        return response($svgContent, 200)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Get QR code SVG file for a subscription.
     */
    public function getQRCodeSVG(int $id): \Illuminate\Http\Response
    {
        $subscription = $this->subscriptionRepository->findById($id);

        if (!$subscription) {
            return response('Subscription not found', 404);
        }

        // Get the latest QR code file for this subscription
        $qrCodeFiles = \Storage::files('qr-codes');
        $subscriptionFiles = array_filter($qrCodeFiles, function ($file) use ($id) {
            return strpos($file, "subscription_{$id}_") === 0;
        });

        if (empty($subscriptionFiles)) {
            // Generate a new QR code if none exists
            $subscriptionData = $this->transformSubscriptionToArray($subscription);
            $qrCodeInfo = $this->qrCodeImageService->generateQRCodeForSubscription(
                $subscription->getId(),
                $subscriptionData
            );

            // Get the filename from the generated QR code info
            $filename = $qrCodeInfo['filename'] ?? null;
            if (!$filename) {
                return response('QR code generation failed', 500);
            }

            $filePath = 'qr-codes/' . $filename;
        } else {
            // Get the most recent file
            $filePath = end($subscriptionFiles);
        }

        if (!\Storage::exists($filePath)) {
            return response('QR code file not found', 404);
        }

        $svgContent = \Storage::get($filePath);

        return response($svgContent, 200)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Verify secure QR code data.
     */
    public function verifyQRCode(Request $request): JsonResponse
    {
        $request->validate([
            'qr_data' => 'required|string'
        ]);

        try {
            $result = $this->qrCodeImageService->verifySecureQRCodeData($request->qr_data);
            
            if ($result['valid']) {
                return response()->json([
                    'success' => true,
                    'data' => $result,
                    'message' => 'QR code is valid'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['error']
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'QR code verification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify secure barcode data.
     */
    public function verifyBarcode(Request $request): JsonResponse
    {
        $request->validate([
            'barcode' => 'required|string'
        ]);

        try {
            $result = $this->qrCodeImageService->verifySecureBarcodeData($request->barcode);

            if ($result['valid']) {
                return response()->json([
                    'success' => true,
                    'data' => $result,
                    'message' => 'Barcode is valid'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['error']
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Barcode verification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate QR codes for all subscriptions.
     */
    public function generateAllQRCodes(): JsonResponse
    {
        try {
            $subscriptions = $this->subscriptionRepository->findAll();
            $qrCodes = [];

            foreach ($subscriptions as $subscription) {
                $subscriptionData = $this->transformSubscriptionToArray($subscription);
                $qrCodeInfo = $this->qrCodeImageService->generateQRCodeForSubscription(
                    $subscription->getId(),
                    $subscriptionData
                );

                $qrCodes[] = [
                    'subscription_id' => $subscription->getId(),
                    'qr_code_image' => $qrCodeInfo,
                    'subscription_data' => $subscriptionData
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $qrCodes,
                'message' => 'QR codes generated for all subscriptions'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating QR codes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transform Subscription entity to array for JSON response.
     */
    private function transformSubscriptionToArray($subscription): array
    {
        // Get subscriber data
        $subscriber = $this->subscriberRepository->findById($subscription->getSubscriberId());
        $subscriberName = $subscriber ? $subscriber->getFullName() : 'Unknown';
        $subscriberData = $subscriber ? [
            'full_name' => $subscriber->getFullName(),
            'type' => $subscriber->getType(),
            'national_id' => $subscriber->getNationalId(),
            'military_id' => $subscriber->getMilitaryId(),
            'phone' => $subscriber->getPhone(),
        ] : null;

        // Get offer data
        $offer = $this->offerRepository->findById($subscription->getOfferId());
        $offerName = $offer ? $offer->getName() : 'Unknown';
        $offerData = $offer ? [
            'name' => $offer->getName(),
            'num_classes' => $offer->getNumClasses(),
            'num_hours' => $offer->getNumHours(),
            'duration_days' => $offer->getDurationDays(),
            'price_infantry' => $offer->getPriceInfantry()->getAmount(),
            'price_civilian' => $offer->getPriceCivilian()->getAmount(),
            'price_other' => $offer->getPriceOther()->getAmount(),
        ] : null;

        // Get academy data
        $academy = $this->academyRepository->findById($subscription->getAcademyId());
        $academyName = $academy ? $academy->getName() : 'Unknown';

        // Get creator data
        $creator = \App\Models\User::find($subscription->getCreatedBy());
        $creatorData = $creator ? [
            'id' => $creator->id,
            'name' => $creator->name,
            'email' => $creator->email,
        ] : null;

        // Generate QR code image data
        $subscriptionData = [
            'id' => $subscription->getId(),
            'subscriber_id' => $subscription->getSubscriberId(),
            'subscriber_name' => $subscriberName,
            'subscriber_data' => $subscriberData,
            'offer_id' => $subscription->getOfferId(),
            'offer_name' => $offerName,
            'offer_data' => $offerData,
            'academy_id' => $subscription->getAcademyId(),
            'academy_name' => $academyName,
            'created_by' => $subscription->getCreatedBy(),
            'creator_data' => $creatorData,
            'start_date' => $subscription->getDuration()->getStartDate()->format('Y-m-d'),
            'end_date' => $subscription->getDuration()->getEndDate()->format('Y-m-d'),
            'chosen_days' => $subscription->getChosenDays(),
            'remaining_classes' => $subscription->getRemainingClasses(),
            'remaining_hours' => $subscription->getRemainingHours(),
            'qr_code' => $subscription->getQrCode()->getValue(),
            'status' => $subscription->getStatus(),
        ];

        // Generate QR code image
        $qrCodeInfo = $this->qrCodeImageService->generateQRCodeForSubscription(
            $subscription->getId(),
            $subscriptionData
        );

        $subscriptionData['qr_code_image'] = $qrCodeInfo;
        
        // Add QR code and barcode SVGs directly to the response
        $subscriptionData['qr_code_svg'] = $qrCodeInfo['svg_data'] ?? null;
        $subscriptionData['barcode_svg'] = $qrCodeInfo['barcode']['svg_data'] ?? null;

        return $subscriptionData;
    }

    /**
     * Record attendance for a subscription
     */
    public function recordAttendance(Request $request): JsonResponse
    {
        $request->validate([
            'scanned_data' => 'required|string'
        ]);

        try {
            $scannedData = $request->input('scanned_data');

            // Parse scanned data to extract subscription ID
            $subscriptionId = $this->parseScannedData($scannedData);
            Log::info('Subscription ID: ' . $subscriptionId);
            Log::info('Scanned Data: ' . $scannedData);

            if (!$subscriptionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات الباركود غير صحيحة'
                ], 400);
            }

            // Get subscription
            $subscription = $this->subscriptionRepository->findById($subscriptionId);
            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'الاشتراك غير موجود'
                ], 404);
            }

            // Check if subscription is active
            if ($subscription->getStatus() !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'الاشتراك غير نشط'
                ], 400);
            }

            // Check if subscription has expired
            $endDate = $subscription->getDuration()->getEndDate();
            if ($endDate->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'انتهت صلاحية الاشتراك في ' . $endDate->format('Y-m-d')
                ], 400);
            }

            // Check if there are remaining classes/hours
            $remainingClasses = $subscription->getRemainingClasses();
            $remainingHours = $subscription->getRemainingHours();

            if ($remainingClasses <= 0 && $remainingHours <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا توجد حصص أو ساعات متبقية في الاشتراك'
                ], 400);
            }

            // Check if attendance already recorded today
            $today = now();
            $startOfDay = $today->copy()->startOfDay();
            $endOfDay = $today->copy()->endOfDay();

            $existingAttendance = $this->attendanceRepository->findBySubscriptionIdAndDateRange(
                $subscriptionId,
                $startOfDay,
                $endOfDay
            );

            if (!empty($existingAttendance)) {
                return response()->json([
                    'success' => false,
                    'message' => 'تم تسجيل الحضور مسبقاً اليوم'
                ], 400);
            }

            // Record attendance
            $attendance = new \Modules\ActivitiesSubscriptions\Domain\Entities\Attendance(
                $subscriptionId,
                now(),
                now()->format('l') // Get current day of week (e.g., 'Monday', 'Tuesday', etc.)
            );

            $this->attendanceRepository->save($attendance);

            // Decrease remaining classes or hours using the checkIn method
            $subscription->checkIn();
            $this->subscriptionRepository->save($subscription);

            // Get updated subscription data
            $updatedSubscription = $this->subscriptionRepository->findById($subscriptionId);
            $subscriptionData = $this->transformSubscriptionToArray($updatedSubscription);

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الحضور بنجاح',
                'subscription' => $subscriptionData,
                'remaining_classes' => $updatedSubscription->getRemainingClasses(),
                'remaining_hours' => $updatedSubscription->getRemainingHours()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تسجيل الحضور: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify subscription information without checking in
     */
    public function verifySubscription(Request $request): JsonResponse
    {
        $request->validate([
            'scanned_data' => 'required|string'
        ]);

        try {
            $scannedData = $request->input('scanned_data');

            // Parse scanned data to extract subscription ID
            $subscriptionId = $this->parseScannedData($scannedData);

            if (!$subscriptionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات الباركود غير صحيحة'
                ], 400);
            }

            // Get subscription
            $subscription = $this->subscriptionRepository->findById($subscriptionId);
            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'الاشتراك غير موجود'
                ], 404);
            }

            // Check if subscription is active
            if ($subscription->getStatus() !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'الاشتراك غير نشط'
                ], 400);
            }

            // Check if subscription has expired
            $endDate = $subscription->getDuration()->getEndDate();
            if ($endDate->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'انتهت صلاحية الاشتراك في ' . $endDate->format('Y-m-d')
                ], 400);
            }

            // Get remaining classes/hours
            $remainingClasses = $subscription->getRemainingClasses();
            $remainingHours = $subscription->getRemainingHours();

            // Get subscription data with related information
            $subscriptionData = $this->transformSubscriptionToArray($subscription);

            return response()->json([
                'success' => true,
                'subscription' => [
                    'id' => $subscription->getId(),
                    'subscriber_name' => $subscriptionData['subscriber_name'] ?? 'غير محدد',
                    'academy_name' => $subscriptionData['academy_name'] ?? 'غير محدد',
                    'offer_name' => $subscriptionData['offer_name'] ?? 'غير محدد',
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

    /**
     * Parse scanned data to extract subscription ID
     * Handles formats: "123" or "123.5" (subscription_id.academy_id)
     */
    private function parseScannedData(string $scannedData): ?int
    {
        // Clean the scanned data (remove extra decimals that scanners might add)
        $cleanData = preg_replace('/\.+/', '.', $scannedData);
        $cleanData = trim($cleanData);

        // Split by dot to handle subscription_id.academy_id format
        $parts = explode('.', $cleanData);

        if (empty($parts[0])) {
            return null;
        }

        // Get the first part as subscription ID
        $subscriptionId = $parts[0];

        // Validate that it's a numeric ID
        if (!is_numeric($subscriptionId) || (int) $subscriptionId <= 0) {
            return null;
        }

        return (int) $subscriptionId;
    }
}
