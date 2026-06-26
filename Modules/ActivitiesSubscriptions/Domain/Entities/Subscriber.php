<?php

namespace Modules\ActivitiesSubscriptions\Domain\Entities;

class Subscriber
{
    private ?int $id = null;
    private string $fullName;
    private string $type;
    private ?string $nationalId;
    private ?string $militaryId;
    private ?string $phone;

    public function __construct(
        string $fullName,
        string $type,
        ?string $nationalId = null,
        ?string $militaryId = null,
        ?string $phone = null
    ) {
        $this->validateSubscriberType($type);
        
        $this->fullName = $fullName;
        $this->type = $type;
        $this->nationalId = $nationalId;
        $this->militaryId = $militaryId;
        $this->phone = $phone;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->validateSubscriberType($type);
        $this->type = $type;
    }

    public function getNationalId(): ?string
    {
        return $this->nationalId;
    }

    public function setNationalId(?string $nationalId): void
    {
        $this->nationalId = $nationalId;
    }

    public function getMilitaryId(): ?string
    {
        return $this->militaryId;
    }

    public function setMilitaryId(?string $militaryId): void
    {
        $this->militaryId = $militaryId;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function getUniqueIdentifier(): ?string
    {
        return $this->nationalId ?? $this->militaryId;
    }

    public function isInfantry(): bool
    {
        return $this->type === 'infantry';
    }

    public function isCivilian(): bool
    {
        return $this->type === 'civilian';
    }

    public function isOther(): bool
    {
        return $this->type === 'other';
    }

    private function validateSubscriberType(string $type): void
    {
        $validTypes = ['infantry', 'civilian', 'other'];
        
        if (!in_array($type, $validTypes)) {
            throw new \InvalidArgumentException("Invalid subscriber type: {$type}. Must be one of: " . implode(', ', $validTypes));
        }
    }
}
