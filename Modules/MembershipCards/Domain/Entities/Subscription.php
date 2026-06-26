<?php

namespace Modules\MembershipCards\Domain\Entities;

use Carbon\Carbon;
use Modules\MembershipCards\Domain\ValueObjects\Duration;
use Modules\MembershipCards\Domain\ValueObjects\Price;

class Subscription
{
    private ?int $id = null;
    private int $officerId;
    private ?int $beneficiaryId;
    private int $feePlanId;
    private Duration $duration;
    private string $status;
    private Price $paidEstablishmentFee;
    private Price $paidAnnualFee;
    private Price $paidIssuanceFee;
    private int|string $createdBy;
    private ?string $notes;
    private bool $isHonoraryMembership;

    public function __construct(
        int $officerId,
        int $feePlanId,
        Duration $duration,
        int|string $createdBy,
        Price $paidEstablishmentFee,
        Price $paidAnnualFee,
        Price $paidIssuanceFee,
        ?int $beneficiaryId = null,
        string $status = 'active',
        ?string $notes = null,
        bool $isHonoraryMembership = false
    ) {
        $this->validateStatus($status);
        
        $this->officerId = $officerId;
        $this->beneficiaryId = $beneficiaryId;
        $this->feePlanId = $feePlanId;
        $this->duration = $duration;
        $this->status = $status;
        $this->paidEstablishmentFee = $paidEstablishmentFee;
        $this->paidAnnualFee = $paidAnnualFee;
        $this->paidIssuanceFee = $paidIssuanceFee;
        $this->createdBy = $createdBy;
        $this->notes = $notes;
        $this->isHonoraryMembership = $isHonoraryMembership;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getOfficerId(): int
    {
        return $this->officerId;
    }

    public function getBeneficiaryId(): ?int
    {
        return $this->beneficiaryId;
    }

    public function getFeePlanId(): int
    {
        return $this->feePlanId;
    }

    public function getDuration(): Duration
    {
        return $this->duration;
    }

    public function getStartDate(): Carbon
    {
        return $this->duration->getStartDate();
    }

    public function getEndDate(): Carbon
    {
        return $this->duration->getEndDate();
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPaidEstablishmentFee(): Price
    {
        return $this->paidEstablishmentFee;
    }

    public function getPaidAnnualFee(): Price
    {
        return $this->paidAnnualFee;
    }

    public function getPaidIssuanceFee(): Price
    {
        return $this->paidIssuanceFee;
    }

    public function getTotalPaidAmount(): Price
    {
        return $this->paidEstablishmentFee
            ->add($this->paidAnnualFee)
            ->add($this->paidIssuanceFee);
    }

    public function getCreatedBy(): int|string
    {
        return $this->createdBy;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function isHonoraryMembership(): bool
    {
        return $this->isHonoraryMembership;
    }

    public function setIsHonoraryMembership(bool $isHonoraryMembership): void
    {
        $this->isHonoraryMembership = $isHonoraryMembership;
    }

    public function isForOfficer(): bool
    {
        return $this->beneficiaryId === null;
    }

    public function isForBeneficiary(): bool
    {
        return $this->beneficiaryId !== null;
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->duration->isActive();
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || $this->duration->isExpired();
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function activate(): void
    {
        $this->status = 'active';
    }

    public function suspend(): void
    {
        $this->status = 'suspended';
    }

    public function expire(): void
    {
        $this->status = 'expired';
    }

    public function extendTo(Carbon $newEndDate): void
    {
        $this->duration->extendTo($newEndDate);
    }

    public function canRenew(): bool
    {
        // Can renew if within 30 days of expiry or already expired (but not suspended)
        return !$this->isSuspended() && 
               ($this->duration->getEndDate()->diffInDays(Carbon::now()) <= 30 || $this->isExpired());
    }

    private function validateStatus(string $status): void
    {
        $validStatuses = ['active', 'expired', 'suspended'];
        
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid subscription status: {$status}");
        }
    }
}

