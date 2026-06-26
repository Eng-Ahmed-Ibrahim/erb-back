<?php

namespace Modules\MembershipCards\Application\DTOs;

class CreateSubscriptionDTO
{
    public function __construct(
        public readonly int $officerId,
        public readonly int $feePlanId,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly int|string $createdBy,
        public readonly ?int $beneficiaryId = null,
        public readonly float $paidEstablishmentFee = 0,
        public readonly float $paidAnnualFee = 0,
        public readonly float $paidIssuanceFee = 0,
        public readonly ?string $notes = null,
        public readonly bool $isHonoraryMembership = false,
        public readonly bool $isOldOfficer = false
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            officerId: $data['officer_id'],
            feePlanId: $data['fee_plan_id'],
            startDate: $data['start_date'],
            endDate: $data['end_date'],
            createdBy: $data['created_by'],
            beneficiaryId: $data['beneficiary_id'] ?? null,
            paidEstablishmentFee: (float) ($data['paid_establishment_fee'] ?? 0),
            paidAnnualFee: (float) ($data['paid_annual_fee'] ?? 0),
            paidIssuanceFee: (float) ($data['paid_issuance_fee'] ?? 0),
            notes: $data['notes'] ?? null,
            isHonoraryMembership: isset($data['is_honorary_membership']) ? (bool) $data['is_honorary_membership'] : false,
            isOldOfficer: isset($data['is_old_officer']) ? (bool) $data['is_old_officer'] : false
        );
    }

    public function toArray(): array
    {
        return [
            'officer_id' => $this->officerId,
            'fee_plan_id' => $this->feePlanId,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'created_by' => $this->createdBy,
            'beneficiary_id' => $this->beneficiaryId,
            'paid_establishment_fee' => $this->paidEstablishmentFee,
            'paid_annual_fee' => $this->paidAnnualFee,
            'paid_issuance_fee' => $this->paidIssuanceFee,
            'notes' => $this->notes,
            'is_old_officer' => $this->isOldOfficer,
        ];
    }
}

