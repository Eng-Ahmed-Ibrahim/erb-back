<?php

namespace Modules\ActivitiesSubscriptions\Domain\Services;

use Modules\ActivitiesSubscriptions\Domain\Entities\Subscription;
use Modules\ActivitiesSubscriptions\Domain\Entities\Offer;
use Modules\ActivitiesSubscriptions\Domain\Entities\Academy;
use Modules\ActivitiesSubscriptions\Domain\Repositories\OfferRepositoryInterface;
use Modules\ActivitiesSubscriptions\Domain\Repositories\AcademyRepositoryInterface;
use Modules\ActivitiesSubscriptions\Domain\Repositories\SubscriptionRepositoryInterface;

class SubscriptionValidationService
{
    public function __construct(
        private OfferRepositoryInterface $offerRepository,
        private AcademyRepositoryInterface $academyRepository,
        private SubscriptionRepositoryInterface $subscriptionRepository
    ) {}

    public function validateSubscriptionCreation(
        int $subscriberId,
        int $offerId,
        int $academyId,
        array $chosenDays
    ): array {
        $errors = [];

        // Validate offer exists and is active
        $offer = $this->offerRepository->findById($offerId);
        if (!$offer) {
            $errors[] = 'Offer not found';
        } elseif (!$offer->isActive()) {
            $errors[] = 'Offer is not active';
        }

        // Validate academy exists and is active
        $academy = $this->academyRepository->findById($academyId);
        if (!$academy) {
            $errors[] = 'Academy not found';
        } elseif (!$academy->isActive()) {
            $errors[] = 'Academy is not active';
        }

        // Validate offer belongs to academy
        if ($offer && $academy && $offer->getAcademyId() !== $academy->getId()) {
            $errors[] = 'Offer does not belong to the specified academy';
        }

        // Validate chosen days are subset of offer available days
        if ($offer) {
            $invalidDays = array_diff($chosenDays, $offer->getAvailableDays());
            if (!empty($invalidDays)) {
                $errors[] = 'Chosen days must be subset of offer available days: ' . implode(', ', $invalidDays);
            }
        }

        // Validate chosen days are subset of academy working days
        if ($academy) {
            $invalidDays = array_diff($chosenDays, $academy->getWorkingDays());
            if (!empty($invalidDays)) {
                $errors[] = 'Chosen days must be subset of academy working days: ' . implode(', ', $invalidDays);
            }
        }

        return $errors;
    }

    public function validateCheckIn(Subscription $subscription, string $dayOfWeek): array
    {
        $errors = [];

        if (!$subscription->isActive()) {
            $errors[] = 'Subscription is not active';
        }

        if (!$subscription->hasRemainingBalance()) {
            $errors[] = 'No remaining balance for check-in';
        }

        if (!in_array($dayOfWeek, $subscription->getChosenDays())) {
            $errors[] = 'Cannot check in on this day of the week';
        }

        return $errors;
    }

    public function canCreateSubscription(
        int $subscriberId,
        int $offerId,
        int $academyId,
        array $chosenDays
    ): bool {
        $errors = $this->validateSubscriptionCreation($subscriberId, $offerId, $academyId, $chosenDays);
        return empty($errors);
    }

    public function canCheckIn(Subscription $subscription, string $dayOfWeek): bool
    {
        $errors = $this->validateCheckIn($subscription, $dayOfWeek);
        return empty($errors);
    }

    public function findDuplicateSubscription(
        int $subscriberId,
        int $offerId,
        string $startDate,
        string $endDate,
        array $chosenDays
    ): ?Subscription {
        // Get all subscriptions for this subscriber
        $subscriberSubscriptions = $this->subscriptionRepository->findBySubscriberId($subscriberId);

        foreach ($subscriberSubscriptions as $subscription) {
            // Check if it's the same offer
            if ($subscription->getOfferId() !== $offerId) {
                continue;
            }

            // Check if dates match
            $subscriptionStartDate = $subscription->getDuration()->getStartDate()->format('Y-m-d');
            $subscriptionEndDate = $subscription->getDuration()->getEndDate()->format('Y-m-d');

            if ($subscriptionStartDate !== $startDate || $subscriptionEndDate !== $endDate) {
                continue;
            }

            // Check if chosen days match
            $subscriptionChosenDays = $subscription->getChosenDays();
            sort($subscriptionChosenDays);
            sort($chosenDays);

            if ($subscriptionChosenDays === $chosenDays) {
                return $subscription; // Found duplicate
            }
        }

        return null; // No duplicate found
    }
}
