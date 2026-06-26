<?php

namespace Modules\MembershipCards\Domain\Services;

use Carbon\Carbon;
use Modules\MembershipCards\Domain\Entities\MembershipCard;
use Modules\MembershipCards\Domain\Entities\Subscription;
use Modules\MembershipCards\Domain\Repositories\SubscriptionRepositoryInterface;
use Modules\MembershipCards\Domain\Repositories\MembershipCardRepositoryInterface;

class CardValidationService
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private MembershipCardRepositoryInterface $cardRepository
    ) {}

    /**
     * Validate card by UID
     */
    public function validateByCardUid(string $cardUid): array
    {
        $card = $this->cardRepository->findByCardUid($cardUid);
        
        if (!$card) {
            return [
                'valid' => false,
                'error' => 'Card not found',
                'card' => null,
                'subscription' => null,
            ];
        }
        
        return $this->validateCard($card);
    }

    /**
     * Validate a membership card
     */
    public function validateCard(MembershipCard $card): array
    {
        $errors = [];
        
        // Check card status
        if ($card->isRevoked()) {
            $errors[] = 'Card has been revoked';
        }
        
        if ($card->isExpired()) {
            $errors[] = 'Card has expired';
        }
        
        // Check subscription
        $subscription = $this->subscriptionRepository->findById($card->getSubscriptionId());
        
        if (!$subscription) {
            $errors[] = 'Associated subscription not found';
        } elseif (!$subscription->isActive()) {
            $errors[] = 'Associated subscription is not active';
        } elseif ($subscription->isExpired()) {
            $errors[] = 'Associated subscription has expired';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'card' => $card,
            'subscription' => $subscription,
            'days_until_expiry' => $card->getDaysUntilExpiry(),
        ];
    }

    /**
     * Check if card can be issued for subscription
     */
    public function canIssueCard(int $subscriptionId): array
    {
        $errors = [];
        
        $subscription = $this->subscriptionRepository->findById($subscriptionId);
        
        if (!$subscription) {
            $errors[] = 'Subscription not found';
            return ['can_issue' => false, 'errors' => $errors];
        }
        
        if (!$subscription->isActive()) {
            $errors[] = 'Subscription is not active';
        }
        
        if ($subscription->isExpired()) {
            $errors[] = 'Subscription has expired';
        }
        
        // Check if card already exists
        $existingCard = $this->cardRepository->findBySubscriptionId($subscriptionId);
        if ($existingCard && $existingCard->isActive()) {
            $errors[] = 'An active card already exists for this subscription';
        }
        
        return [
            'can_issue' => empty($errors),
            'errors' => $errors,
            'subscription' => $subscription,
        ];
    }

    /**
     * Validate card UID format
     */
    public function validateCardUidFormat(string $cardUid): bool
    {
        $cleanValue = str_replace([':', '-', ' '], '', $cardUid);
        
        if (!preg_match('/^[0-9A-Fa-f]+$/', $cleanValue)) {
            return false;
        }
        
        // MIFARE UIDs are typically 4, 7, or 10 bytes
        $validLengths = [8, 14, 20]; // In hex characters
        
        return in_array(strlen($cleanValue), $validLengths);
    }

    /**
     * Get card status summary
     */
    public function getCardStatus(MembershipCard $card): array
    {
        return [
            'id' => $card->getId(),
            'card_uid' => $card->getCardUid()->getValue(),
            'status' => $card->getStatus(),
            'is_active' => $card->isActive(),
            'is_expired' => $card->isExpired(),
            'is_revoked' => $card->isRevoked(),
            'is_printed' => $card->isPrinted(),
            'is_encoded' => $card->isEncoded(),
            'is_ready' => $card->isReady(),
            'expiry_date' => $card->getExpiryDate()->format('Y-m-d'),
            'days_until_expiry' => $card->getDaysUntilExpiry(),
        ];
    }

    /**
     * Find cards expiring soon
     */
    public function findExpiringCards(int $daysAhead = 30): array
    {
        return $this->cardRepository->findExpiring($daysAhead);
    }

    /**
     * Find expired cards that need status update
     */
    public function findExpiredCards(): array
    {
        return $this->cardRepository->findExpired();
    }

    /**
     * Update expired card statuses
     */
    public function expireCards(): int
    {
        $expiredCards = $this->findExpiredCards();
        $count = 0;
        
        foreach ($expiredCards as $card) {
            $card->expire();
            $this->cardRepository->save($card);
            $count++;
        }
        
        return $count;
    }
}

