<?php

namespace Modules\ActivitiesSubscriptions\Application\DTOs;

class CreateSubscriberDTO
{
    public function __construct(
        public readonly string $fullName,
        public readonly string $type,
        public readonly ?string $nationalId = null,
        public readonly ?string $militaryId = null,
        public readonly ?string $phone = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            fullName: $data['full_name'],
            type: $data['type'],
            nationalId: $data['national_id'] ?? null,
            militaryId: $data['military_id'] ?? null,
            phone: $data['phone'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'full_name' => $this->fullName,
            'type' => $this->type,
            'national_id' => $this->nationalId,
            'military_id' => $this->militaryId,
            'phone' => $this->phone,
        ];
    }
}
