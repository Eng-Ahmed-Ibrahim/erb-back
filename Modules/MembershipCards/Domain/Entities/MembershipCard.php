<?php

namespace Modules\MembershipCards\Domain\Entities;

use Carbon\Carbon;
use Modules\MembershipCards\Domain\ValueObjects\CardUID;

class MembershipCard
{
    private ?int $id = null;
    private int $subscriptionId;
    private CardUID $cardUid;
    private ?Carbon $printedAt;
    private ?Carbon $encodedAt;
    private Carbon $expiryDate;
    private string $status;
    private ?array $encodedData;
    private ?string $notes;
    private ?string $cardToken;
    private ?string $cardTokenHex;
    private ?string $serialId;
    private bool $isReplacement;
    private bool $showExpiryDate;

    public function __construct(
        int $subscriptionId,
        CardUID $cardUid,
        Carbon $expiryDate,
        string $status = 'active',
        ?Carbon $printedAt = null,
        ?Carbon $encodedAt = null,
        ?array $encodedData = null,
        ?string $notes = null,
        ?string $cardToken = null,
        ?string $cardTokenHex = null,
        ?string $serialId = null,
        bool $isReplacement = false,
        bool $showExpiryDate = true
    ) {
        $this->validateStatus($status);
        
        $this->subscriptionId = $subscriptionId;
        $this->cardUid = $cardUid;
        $this->expiryDate = $expiryDate;
        $this->status = $status;
        $this->printedAt = $printedAt;
        $this->encodedAt = $encodedAt;
        $this->encodedData = $encodedData;
        $this->notes = $notes;
        $this->cardToken = $cardToken;
        $this->cardTokenHex = $cardTokenHex;
        $this->serialId = $serialId;
        $this->isReplacement = $isReplacement;
        $this->showExpiryDate = $showExpiryDate;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getSubscriptionId(): int
    {
        return $this->subscriptionId;
    }

    public function getCardUid(): CardUID
    {
        return $this->cardUid;
    }

    public function getPrintedAt(): ?Carbon
    {
        return $this->printedAt;
    }

    public function markAsPrinted(): void
    {
        $this->printedAt = Carbon::now();
    }

    public function getEncodedAt(): ?Carbon
    {
        return $this->encodedAt;
    }

    public function markAsEncoded(array $encodedData): void
    {
        $this->encodedAt = Carbon::now();
        $this->encodedData = $encodedData;
    }

    public function getExpiryDate(): Carbon
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(Carbon $expiryDate): void
    {
        $this->expiryDate = $expiryDate;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getEncodedData(): ?array
    {
        return $this->encodedData;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function getCardToken(): ?string
    {
        return $this->cardToken;
    }

    public function setCardToken(?string $cardToken): void
    {
        $this->cardToken = $cardToken;
    }

    public function getCardTokenHex(): ?string
    {
        return $this->cardTokenHex;
    }

    public function setCardTokenHex(?string $cardTokenHex): void
    {
        $this->cardTokenHex = $cardTokenHex;
    }

    public function getSerialId(): ?string
    {
        return $this->serialId;
    }

    public function setSerialId(?string $serialId): void
    {
        $this->serialId = $serialId;
    }

    public function isReplacement(): bool
    {
        return $this->isReplacement;
    }

    public function setIsReplacement(bool $isReplacement): void
    {
        $this->isReplacement = $isReplacement;
    }

    public function getShowExpiryDate(): bool
    {
        return $this->showExpiryDate;
    }

    public function setShowExpiryDate(bool $showExpiryDate): void
    {
        $this->showExpiryDate = $showExpiryDate;
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || Carbon::now()->isAfter($this->expiryDate);
    }

    public function isPrinted(): bool
    {
        return $this->printedAt !== null;
    }

    public function isEncoded(): bool
    {
        return $this->encodedAt !== null;
    }

    public function isReady(): bool
    {
        return $this->isPrinted() && $this->isEncoded() && $this->isActive();
    }

    public function activate(): void
    {
        $this->status = 'active';
    }

    public function revoke(): void
    {
        $this->status = 'revoked';
    }

    public function expire(): void
    {
        $this->status = 'expired';
    }

    public function getDaysUntilExpiry(): int
    {
        return max(0, Carbon::now()->diffInDays($this->expiryDate, false));
    }

    private function validateStatus(string $status): void
    {
        $validStatuses = ['active', 'revoked', 'expired'];
        
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid card status: {$status}");
        }
    }
}

