<?php

namespace Modules\MembershipCards\Application\DTOs;

class CreateFeePlanDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $beneficiaryType,
        public readonly float $establishmentFee,
        public readonly float $annualSubscriptionFee,
        public readonly float $issuanceFee,
        public readonly bool $active = true,
        public readonly ?string $description = null,
        public readonly ?array $ageRange = null,
        public readonly ?string $weaponType = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            beneficiaryType: $data['beneficiary_type'],
            establishmentFee: (float) ($data['establishment_fee'] ?? 0),
            annualSubscriptionFee: (float) ($data['annual_subscription_fee'] ?? 0),
            issuanceFee: (float) ($data['issuance_fee'] ?? 0),
            active: $data['active'] ?? true,
            description: $data['description'] ?? null,
            ageRange: $data['age_range'] ?? null,
            weaponType: $data['weapon_type'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'beneficiary_type' => $this->beneficiaryType,
            'establishment_fee' => $this->establishmentFee,
            'annual_subscription_fee' => $this->annualSubscriptionFee,
            'issuance_fee' => $this->issuanceFee,
            'active' => $this->active,
            'description' => $this->description,
            'age_range' => $this->ageRange,
            'weapon_type' => $this->weaponType,
        ];
    }
}

