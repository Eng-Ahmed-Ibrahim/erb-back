<?php

namespace Modules\ActivitiesSubscriptions\Application\Handlers;

use Modules\ActivitiesSubscriptions\Application\Commands\CreateSubscriptionCommand;
use Modules\ActivitiesSubscriptions\Domain\Entities\Subscription;
use Modules\ActivitiesSubscriptions\Domain\ValueObjects\Duration;
use Modules\ActivitiesSubscriptions\Domain\ValueObjects\QRCode;
use Modules\ActivitiesSubscriptions\Domain\Repositories\SubscriptionRepositoryInterface;
use Modules\ActivitiesSubscriptions\Domain\Services\QRCodeService;
use Modules\ActivitiesSubscriptions\Domain\Services\SubscriptionValidationService;

class CreateSubscriptionHandler
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private QRCodeService $qrCodeService,
        private SubscriptionValidationService $validationService
    ) {}

    public function handle(CreateSubscriptionCommand $command): Subscription
    {
        $dto = $command->subscriptionDTO;

        // Check for duplicate subscription
        $duplicateSubscription = $this->validationService->findDuplicateSubscription(
            $dto->subscriberId,
            $dto->offerId,
            $dto->startDate->format('Y-m-d'),
            $dto->endDate->format('Y-m-d'),
            $dto->chosenDays
        );

        if ($duplicateSubscription) {
            // Return existing subscription instead of creating a new one
            return $duplicateSubscription;
        }

        // Validate subscription creation
        $errors = $this->validationService->validateSubscriptionCreation(
            $dto->subscriberId,
            $dto->offerId,
            $dto->academyId,
            $dto->chosenDays
        );
        
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Validation failed: ' . implode(', ', $errors));
        }
        
        // Generate QR code (will be updated with actual subscription ID after save)
        $tempQrCode = $this->qrCodeService->generateQRCode(0);
        
        $subscription = new Subscription(
            subscriberId: $dto->subscriberId,
            offerId: $dto->offerId,
            academyId: $dto->academyId,
            createdBy: $dto->createdBy,
            duration: new Duration($dto->startDate, $dto->endDate),
            chosenDays: $dto->chosenDays,
            initialClasses: $dto->initialClasses,
            initialHours: $dto->initialHours,
            qrCode: $tempQrCode,
            status: $dto->status
        );

        $savedSubscription = $this->subscriptionRepository->save($subscription);
        
        // Generate final QR code with actual subscription ID
        $finalQrCode = $this->qrCodeService->generateQRCode($savedSubscription->getId());

        // Create a new subscription with the final QR code
        $updatedSubscription = new Subscription(
            subscriberId: $savedSubscription->getSubscriberId(),
            offerId: $savedSubscription->getOfferId(),
            academyId: $savedSubscription->getAcademyId(),
            createdBy: $savedSubscription->getCreatedBy(),
            duration: $savedSubscription->getDuration(),
            chosenDays: $savedSubscription->getChosenDays(),
            initialClasses: $savedSubscription->getRemainingClasses(),
            initialHours: $savedSubscription->getRemainingHours(),
            qrCode: $finalQrCode,
            status: $savedSubscription->getStatus()
        );

        // Set the ID to maintain the same subscription
        $updatedSubscription->setId($savedSubscription->getId());

        return $this->subscriptionRepository->save($updatedSubscription);
    }
}
