<?php

namespace Modules\ActivitiesSubscriptions\Application\Handlers;

use Modules\ActivitiesSubscriptions\Application\Commands\CheckInCommand;
use Modules\ActivitiesSubscriptions\Domain\Entities\Attendance;
use Modules\ActivitiesSubscriptions\Domain\Repositories\SubscriptionRepositoryInterface;
use Modules\ActivitiesSubscriptions\Domain\Repositories\AttendanceRepositoryInterface;
use Modules\ActivitiesSubscriptions\Domain\Services\QRCodeService;
use Modules\ActivitiesSubscriptions\Domain\Services\SubscriptionValidationService;

class CheckInHandler
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private AttendanceRepositoryInterface $attendanceRepository,
        private QRCodeService $qrCodeService,
        private SubscriptionValidationService $validationService
    ) {}

    public function handle(CheckInCommand $command): Attendance
    {
        // Validate QR code
        if (!$this->qrCodeService->isQRCodeValid($command->qrCode)) {
            throw new \InvalidArgumentException('Invalid QR code');
        }
        
        // Extract subscription ID from QR code
        $subscriptionId = $this->qrCodeService->extractSubscriptionId($command->qrCode);
        if (!$subscriptionId) {
            throw new \InvalidArgumentException('Could not extract subscription ID from QR code');
        }
        
        // Find subscription
        $subscription = $this->subscriptionRepository->findById($subscriptionId);
        if (!$subscription) {
            throw new \InvalidArgumentException('Subscription not found');
        }
        
        // Validate check-in
        $errors = $this->validationService->validateCheckIn($subscription, $command->dayOfWeek);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Check-in validation failed: ' . implode(', ', $errors));
        }
        
        // Perform check-in
        $subscription->checkIn();
        $this->subscriptionRepository->save($subscription);
        
        // Create attendance record
        $attendance = new Attendance(
            subscriptionId: $subscription->getId(),
            checkInDate: $command->checkInDate,
            dayOfWeek: $command->dayOfWeek
        );
        
        return $this->attendanceRepository->save($attendance);
    }
}
