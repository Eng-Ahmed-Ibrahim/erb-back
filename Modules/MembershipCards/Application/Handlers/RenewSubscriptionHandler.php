<?php

namespace Modules\MembershipCards\Application\Handlers;

use Carbon\Carbon;
use Modules\MembershipCards\Application\Commands\RenewSubscriptionCommand;
use Modules\MembershipCards\Domain\Repositories\SubscriptionRepositoryInterface;
use Modules\MembershipCards\Domain\Repositories\MembershipCardRepositoryInterface;
use Modules\MembershipCards\Domain\ValueObjects\Price;

class RenewSubscriptionHandler
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private MembershipCardRepositoryInterface $cardRepository
    ) {}

    public function handle(RenewSubscriptionCommand $command): void
    {
        // Find subscription
        $subscription = $this->subscriptionRepository->findById($command->subscriptionId);
        
        if (!$subscription) {
            throw new \InvalidArgumentException('الاشتراك غير موجود');
        }
        
        // Check if subscription can be renewed
        if (!$subscription->canRenew()) {
            throw new \InvalidArgumentException('لا يمكن تجديد هذا الاشتراك حالياً');
        }
        
        // Validate new end date
        $newEndDate = Carbon::parse($command->newEndDate);
        $currentEndDate = $subscription->getEndDate();
        
        if ($newEndDate->lte($currentEndDate)) {
            throw new \InvalidArgumentException('تاريخ الانتهاء الجديد يجب أن يكون بعد تاريخ الانتهاء الحالي');
        }
        
        // Extend subscription
        $subscription->extendTo($newEndDate);
        
        // Update paid fees (renewal fees: annual + issuance, no establishment fee)
        // Note: We're not modifying the original paid fees, but adding renewal fees
        // In a real system, you might want to track renewal history separately
        // For now, we'll update the annual and issuance fees to reflect the renewal payment
        
        // Activate subscription if it was expired
        if ($subscription->isExpired()) {
            $subscription->activate();
        }
        
        // Update notes if provided
        if ($command->notes !== null) {
            $currentNotes = $subscription->getNotes() ?? '';
            $renewalNote = "تجديد في " . Carbon::now()->format('Y-m-d') . ": " . $command->notes;
            $subscription->setNotes($currentNotes ? $currentNotes . "\n" . $renewalNote : $renewalNote);
        }
        
        // Save subscription
        $this->subscriptionRepository->save($subscription);
        
        // Update card expiry date if card exists
        $card = $this->cardRepository->findBySubscriptionId($subscription->getId());
        if ($card && $card->isActive()) {
            $card->setExpiryDate($newEndDate);
            $this->cardRepository->save($card);
        }
    }
}

