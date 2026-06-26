<?php

namespace Modules\MembershipCards\Domain\Entities;

use Modules\MembershipCards\Domain\ValueObjects\MilitaryNumber;

class Officer
{
    private ?int $id = null;
    private string $nationalId;
    private string $fullName;
    private string $rank;
    private string $weaponType;
    private ?string $seniorityNumber;
    private MilitaryNumber $militaryNumber;
    private ?string $membershipId;
    private ?int $age;
    private ?string $notes;
    private ?string $photo;
    private ?string $serviceStatus;
    private bool $isStaffOfficer;

    public function __construct(
        string $nationalId,
        string $fullName,
        string $rank,
        string $weaponType,
        MilitaryNumber $militaryNumber,
        ?string $seniorityNumber = null,
        ?string $membershipId = null,
        ?int $age = null,
        ?string $notes = null,
        ?string $photo = null,
        ?string $serviceStatus = null,
        bool $isStaffOfficer = false
    ) {
        $this->validateNationalId($nationalId);
        $this->validateWeaponType($weaponType);
        
        $this->nationalId = $nationalId;
        $this->fullName = $fullName;
        $this->rank = $rank;
        $this->weaponType = $weaponType;
        $this->militaryNumber = $militaryNumber;
        $this->seniorityNumber = $seniorityNumber;
        $this->membershipId = $membershipId;
        $this->age = $age;
        $this->notes = $notes;
        $this->photo = $photo;
        $this->serviceStatus = $serviceStatus;
        $this->isStaffOfficer = $isStaffOfficer;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getNationalId(): string
    {
        return $this->nationalId;
    }

    public function setNationalId(string $nationalId): void
    {
        $this->validateNationalId($nationalId);
        $this->nationalId = $nationalId;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

    public function getRank(): string
    {
        return $this->rank;
    }

    public function setRank(string $rank): void
    {
        $this->rank = $rank;
    }

    public function getWeaponType(): string
    {
        return $this->weaponType;
    }

    public function setWeaponType(string $weaponType): void
    {
        $this->validateWeaponType($weaponType);
        $this->weaponType = $weaponType;
    }

    public function getSeniorityNumber(): ?string
    {
        return $this->seniorityNumber;
    }

    public function setSeniorityNumber(?string $seniorityNumber): void
    {
        $this->seniorityNumber = $seniorityNumber;
    }

    public function getMilitaryNumber(): MilitaryNumber
    {
        return $this->militaryNumber;
    }

    public function setMilitaryNumber(MilitaryNumber $militaryNumber): void
    {
        $this->militaryNumber = $militaryNumber;
    }

    public function getMembershipId(): ?string
    {
        return $this->membershipId;
    }

    public function setMembershipId(?string $membershipId): void
    {
        $this->membershipId = $membershipId;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): void
    {
        $this->age = $age;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): void
    {
        $this->photo = $photo;
    }

    public function getServiceStatus(): ?string
    {
        return $this->serviceStatus;
    }

    public function setServiceStatus(?string $serviceStatus): void
    {
        $this->serviceStatus = $serviceStatus;
    }

    public function isStaffOfficer(): bool
    {
        return $this->isStaffOfficer;
    }

    public function setIsStaffOfficer(bool $isStaffOfficer): void
    {
        $this->isStaffOfficer = $isStaffOfficer;
    }

    public function isInfantry(): bool
    {
        return $this->weaponType === 'infantry';
    }

    public function getFamilyIndex(): int
    {
        // Officers always have family index 0
        return 0;
    }

    private function validateNationalId(string $nationalId): void
    {
        if (!preg_match('/^\d{14}$/', $nationalId)) {
            throw new \InvalidArgumentException('National ID must be exactly 14 digits');
        }
    }

    private function validateWeaponType(string $weaponType): void
    {
        $validTypes = ['infantry', 'other'];
        
        if (!in_array($weaponType, $validTypes)) {
            throw new \InvalidArgumentException("Invalid weapon type: {$weaponType}. Must be one of: " . implode(', ', $validTypes));
        }
    }
}
