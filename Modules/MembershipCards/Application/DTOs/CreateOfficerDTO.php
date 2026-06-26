<?php

namespace Modules\MembershipCards\Application\DTOs;

class CreateOfficerDTO
{
    public function __construct(
        public readonly string $nationalId,
        public readonly string $fullName,
        public readonly string $rank,
        public readonly string $weaponType,
        public readonly ?string $seniorityNumber = null,
        public readonly ?string $militaryNumber = null,
        public readonly ?string $membershipId = null,
        public readonly ?int $age = null,
        public readonly ?string $notes = null,
        public readonly ?string $photo = null,
        public readonly ?string $serviceStatus = null,
        public readonly bool $isStaffOfficer = false
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nationalId: $data['national_id'],
            fullName: $data['full_name'],
            rank: $data['rank'],
            weaponType: $data['weapon_type'] ?? 'infantry',
            seniorityNumber: $data['seniority_number'] ?? null,
            militaryNumber: $data['military_number'] ?? null,
            membershipId: $data['membership_id'] ?? null,
            age: $data['age'] ?? null,
            notes: $data['notes'] ?? null,
            photo: $data['photo'] ?? null,
            serviceStatus: $data['service_status'] ?? null,
            isStaffOfficer: isset($data['is_staff_officer']) ? (bool) $data['is_staff_officer'] : false
        );
    }

    public function toArray(): array
    {
        return [
            'national_id' => $this->nationalId,
            'full_name' => $this->fullName,
            'rank' => $this->rank,
            'weapon_type' => $this->weaponType,
            'seniority_number' => $this->seniorityNumber,
            'military_number' => $this->militaryNumber,
            'membership_id' => $this->membershipId,
            'age' => $this->age,
            'notes' => $this->notes,
            'photo' => $this->photo,
            'service_status' => $this->serviceStatus,
            'is_staff_officer' => $this->isStaffOfficer,
        ];
    }
}
