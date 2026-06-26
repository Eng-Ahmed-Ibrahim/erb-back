<?php

namespace Modules\MembershipCards\Application\Handlers;

use Carbon\Carbon;
use Modules\MembershipCards\Application\Commands\CreateMembershipCardCommand;
use Modules\MembershipCards\Domain\Entities\MembershipCard;
use Modules\MembershipCards\Domain\Repositories\MembershipCardRepositoryInterface;
use Modules\MembershipCards\Domain\Repositories\SubscriptionRepositoryInterface;
use Modules\MembershipCards\Domain\Services\CardWriterService;
use Modules\MembershipCards\Domain\ValueObjects\CardUID;
use Illuminate\Support\Facades\Log;

class CreateMembershipCardHandler
{
    public function __construct(
        private MembershipCardRepositoryInterface $cardRepository,
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private CardWriterService $cardWriterService
    ) {}

    public function handle(CreateMembershipCardCommand $command): MembershipCard
    {
        $dto = $command->cardDTO;
        
        // Validate subscription exists
        $subscription = $this->subscriptionRepository->findById($dto->subscriptionId);
        if (!$subscription) {
            throw new \InvalidArgumentException('الاشتراك غير موجود');
        }
        
        // Validate subscription is active
        if (!$subscription->isActive()) {
            throw new \InvalidArgumentException('الاشتراك غير نشط');
        }
        
        // Check if card already exists for this subscription
        // If it's a replacement (isReplacement flag), revoke the old card
        $existingCard = $this->cardRepository->findBySubscriptionId($dto->subscriptionId);
        if ($existingCard) {
            if (isset($dto->isReplacement) && $dto->isReplacement) {
                // Revoke the old card when issuing a replacement
                $existingCard->revoke();
                $this->cardRepository->save($existingCard);
            } else {
                throw new \InvalidArgumentException('تم إصدار بطاقة لهذا الاشتراك مسبقاً');
            }
        }
        
        // Check if card UID is already in use
        if ($this->cardRepository->existsByCardUid($dto->cardUid)) {
            throw new \InvalidArgumentException('رقم البطاقة مستخدم بالفعل');
        }

        // Generate unique token/hash for the subscription
        $tokenData = $this->generateCardToken($subscription->getId(), $dto->cardUid);
        $tokenHex = $this->convertToHex($tokenData);

        // Initialize card with token and serial_id
        $card = new MembershipCard(
            subscriptionId: $dto->subscriptionId,
            cardUid: new CardUID($dto->cardUid),
            expiryDate: Carbon::parse($dto->expiryDate),
            status: 'active',
            printedAt: null,
            encodedAt: null,
            encodedData: null,
            notes: $dto->notes,
            cardToken: $tokenData,
            cardTokenHex: $tokenHex,
            serialId: $dto->serialId,
            isReplacement: $dto->isReplacement ?? false,
            showExpiryDate: $dto->showExpiryDate
        );

        // Save card with token (token will be written to card later via encode action)
        $card = $this->cardRepository->save($card);

        return $card;
    }

    /**
     * Generate a unique token/hash for the subscription
     * 
     * @param int $subscriptionId
     * @param string $cardUid
     * @return string
     */
    private function generateCardToken(int $subscriptionId, string $cardUid): string
    {
        // Create a unique token based on subscription ID, card UID, and timestamp
        $data = [
            'subscription_id' => $subscriptionId,
            'card_uid' => $cardUid,
            'timestamp' => Carbon::now()->timestamp,
            'random' => bin2hex(random_bytes(4)),
        ];

        // Create a hash
        $hash = hash('sha256', json_encode($data));

        return $hash;
    }

    /**
     * Convert token to 16-byte HEX string (32 hex characters)
     * 
     * @param string $token
     * @return string
     */
    private function convertToHex(string $token): string
    {
        // Take first 16 bytes (32 hex chars) from the hash
        return strtoupper(substr($token, 0, 32));
    }
}

