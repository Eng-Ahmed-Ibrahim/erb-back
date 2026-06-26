<?php

namespace Modules\MembershipCards\Domain\Entities;

use Carbon\Carbon;

class Beneficiary
{
    private ?int $id = null;
    private int $officerId;
    private string $fullName;
    private string $relationshipType;
    private ?Carbon $birthDate;
    private ?string $nationalId;
    private ?int $familyIndex;
    private ?string $notes;
    private ?string $photo;

    public function __construct(
        int $officerId,
        string $fullName,
        string $relationshipType,
        ?int $familyIndex = null,
        ?Carbon $birthDate = null,
        ?string $nationalId = null,
        ?string $notes = null,
        ?string $photo = null
    ) {
        $this->validateRelationshipType($relationshipType);
        if ($familyIndex !== null) {
            $this->validateFamilyIndex($familyIndex);
        }
        
        if ($nationalId !== null) {
            $this->validateNationalId($nationalId);
        }
        
        $this->officerId = $officerId;
        $this->fullName = $fullName;
        $this->relationshipType = $relationshipType;
        $this->familyIndex = $familyIndex;
        $this->birthDate = $birthDate;
        $this->nationalId = $nationalId;
        $this->notes = $notes;
        $this->photo = $photo;
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

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

    public function getRelationshipType(): string
    {
        return $this->relationshipType;
    }

    public function setRelationshipType(string $relationshipType): void
    {
        $this->validateRelationshipType($relationshipType);
        $this->relationshipType = $relationshipType;
    }

    public function getBirthDate(): ?Carbon
    {
        return $this->birthDate;
    }

    public function setBirthDate(?Carbon $birthDate): void
    {
        $this->birthDate = $birthDate;
    }

    public function getNationalId(): ?string
    {
        return $this->nationalId;
    }

    public function setNationalId(?string $nationalId): void
    {
        if ($nationalId !== null) {
            $this->validateNationalId($nationalId);
        }
        $this->nationalId = $nationalId;
    }

    public function getFamilyIndex(): ?int
    {
        return $this->familyIndex;
    }

    public function setFamilyIndex(?int $familyIndex): void
    {
        if ($familyIndex !== null) {
            $this->validateFamilyIndex($familyIndex);
        }
        $this->familyIndex = $familyIndex;
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

    public function getAge(): ?int
    {
        if ($this->birthDate === null) {
            return null;
        }
        return $this->birthDate->age;
    }

    public function isSpouse(): bool
    {
        return $this->relationshipType === 'spouse';
    }

    public function isChild(): bool
    {
        return $this->relationshipType === 'child';
    }

    public function isParent(): bool
    {
        return $this->relationshipType === 'parent';
    }

    public function isGrandchild(): bool
    {
        return $this->relationshipType === 'grandchild';
    }

    public function isChildSpouse(): bool
    {
        return $this->relationshipType === 'child_spouse';
    }

    public function isUnder21(): bool
    {
        $age = $this->getAge();
        return $age !== null && $age < 21;
    }

    public function isInAgeRange(int $min, int $max): bool
    {
        $age = $this->getAge();
        return $age !== null && $age >= $min && $age <= $max;
    }

    private function validateRelationshipType(string $relationshipType): void
    {
        $validTypes = ['spouse', 'child', 'parent', 'grandchild', 'child_spouse', 'brother', 'sister', 'sister_spouse', 'over_age'];
        
        if (!in_array($relationshipType, $validTypes)) {
            throw new \InvalidArgumentException("Invalid relationship type: {$relationshipType}. Must be one of: " . implode(', ', $validTypes));
        }
    }

    private function validateFamilyIndex(int $familyIndex): void
    {
        if ($familyIndex < 1) {
            throw new \InvalidArgumentException('Family index must be at least 1 for beneficiaries');
        }
    }

    private function validateNationalId(string $nationalId): void
    {
        if (!preg_match('/^\d{14}$/', $nationalId)) {
            throw new \InvalidArgumentException('National ID must be exactly 14 digits');
        }
    }
}

