<?php

namespace Modules\MembershipCards\Application\DTOs;

class CreateBeneficiaryDTO
{
    public function __construct(
        public readonly int $officerId,
        public readonly string $fullName,
        public readonly string $relationshipType,
        public readonly ?string $birthDate = null,
        public readonly ?string $nationalId = null,
        public readonly ?int $familyIndex = null,
        public readonly ?string $notes = null,
        public readonly ?string $photo = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            officerId: $data['officer_id'],
            fullName: $data['full_name'],
            relationshipType: $data['relationship_type'],
            birthDate: $data['birth_date'] ?? null,
            nationalId: $data['national_id'] ?? null,
            familyIndex: isset($data['family_index']) ? (int) $data['family_index'] : null,
            notes: $data['notes'] ?? null,
            photo: $data['photo'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'officer_id' => $this->officerId,
            'full_name' => $this->fullName,
            'relationship_type' => $this->relationshipType,
            'birth_date' => $this->birthDate,
            'national_id' => $this->nationalId,
            'notes' => $this->notes,
            'photo' => $this->photo,
        ];
    }
}

