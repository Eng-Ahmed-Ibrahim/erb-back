<?php

namespace Modules\MembershipCards\Domain\Entities;

use Modules\MembershipCards\Domain\ValueObjects\Price;

class FeePlan
{
    private ?int $id = null;
    private string $name;
    private string $beneficiaryType;
    private string $weaponType;
    private Price $establishmentFee;
    private Price $annualSubscriptionFee;
    private Price $issuanceFee;
    private int $version;
    private bool $active;
    private ?string $description;
    private ?array $ageRange;

    public function __construct(
        string $name,
        string $beneficiaryType,
        Price $establishmentFee,
        Price $annualSubscriptionFee,
        Price $issuanceFee,
        string $weaponType = 'infantry',
        int $version = 1,
        bool $active = true,
        ?string $description = null,
        ?array $ageRange = null
    ) {
        $this->name = $name;
        $this->beneficiaryType = $beneficiaryType;
        $this->weaponType = $weaponType;
        $this->establishmentFee = $establishmentFee;
        $this->annualSubscriptionFee = $annualSubscriptionFee;
        $this->issuanceFee = $issuanceFee;
        $this->version = $version;
        $this->active = $active;
        $this->description = $description;
        $this->ageRange = $ageRange;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getBeneficiaryType(): string
    {
        return $this->beneficiaryType;
    }

    public function setBeneficiaryType(string $beneficiaryType): void
    {
        $this->beneficiaryType = $beneficiaryType;
    }

    public function getWeaponType(): string
    {
        return $this->weaponType;
    }

    public function setWeaponType(string $weaponType): void
    {
        $this->weaponType = $weaponType;
    }

    public function isInfantry(): bool
    {
        return $this->weaponType === 'infantry';
    }

    public function getEstablishmentFee(): Price
    {
        return $this->establishmentFee;
    }

    public function setEstablishmentFee(Price $establishmentFee): void
    {
        $this->establishmentFee = $establishmentFee;
    }

    public function getAnnualSubscriptionFee(): Price
    {
        return $this->annualSubscriptionFee;
    }

    public function setAnnualSubscriptionFee(Price $annualSubscriptionFee): void
    {
        $this->annualSubscriptionFee = $annualSubscriptionFee;
    }

    public function getIssuanceFee(): Price
    {
        return $this->issuanceFee;
    }

    public function setIssuanceFee(Price $issuanceFee): void
    {
        $this->issuanceFee = $issuanceFee;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function incrementVersion(): void
    {
        $this->version++;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function activate(): void
    {
        $this->active = true;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getAgeRange(): ?array
    {
        return $this->ageRange;
    }

    public function setAgeRange(?array $ageRange): void
    {
        $this->ageRange = $ageRange;
    }

    public function getTotalInitialFee(): Price
    {
        return $this->establishmentFee
            ->add($this->annualSubscriptionFee)
            ->add($this->issuanceFee);
    }

    public function getRenewalFee(): Price
    {
        return $this->annualSubscriptionFee->add($this->issuanceFee);
    }

    public function matchesAge(?int $age): bool
    {
        if ($this->ageRange === null) {
            return true;
        }

        if ($age === null) {
            return false;
        }

        $min = $this->ageRange['min'] ?? 0;
        $max = $this->ageRange['max'] ?? PHP_INT_MAX;

        return $age >= $min && $age <= $max;
    }
}

