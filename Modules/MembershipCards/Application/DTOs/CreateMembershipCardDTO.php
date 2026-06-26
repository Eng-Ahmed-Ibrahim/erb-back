<?php

namespace Modules\MembershipCards\Application\DTOs;

class CreateMembershipCardDTO
{
    public function __construct(
        public readonly int $subscriptionId,
        public readonly string $cardUid,
        public readonly string $expiryDate,
        public readonly ?string $notes = null,
        public readonly ?string $serialId = null,
        public readonly bool $isReplacement = false,
        public readonly bool $showExpiryDate = true
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            subscriptionId: $data['subscription_id'],
            cardUid: $data['card_uid'],
            expiryDate: $data['expiry_date'],
            notes: $data['notes'] ?? null,
            serialId: $data['serial_id'] ?? null,
            isReplacement: $data['is_replacement'] ?? false,
            showExpiryDate: isset($data['show_expiry_date']) ? (bool) $data['show_expiry_date'] : true
        );
    }

    public function toArray(): array
    {
        return [
            'subscription_id' => $this->subscriptionId,
            'card_uid' => $this->cardUid,
            'expiry_date' => $this->expiryDate,
            'notes' => $this->notes,
        ];
    }
}

