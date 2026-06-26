<?php

namespace Modules\ActivitiesSubscriptions\Application\DTOs;

class CreateCoachDTO
{
    public function __construct(
        public readonly int $academyId,
        public readonly string $name,
        public readonly ?string $phone = null,
        public readonly ?string $bio = null,
        public readonly bool $active = true
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            academyId: $data['academy_id'],
            name: $data['name'],
            phone: $data['phone'] ?? null,
            bio: $data['bio'] ?? null,
            active: $data['active'] ?? true
        );
    }

    public function toArray(): array
    {
        return [
            'academy_id' => $this->academyId,
            'name' => $this->name,
            'phone' => $this->phone,
            'bio' => $this->bio,
            'active' => $this->active,
        ];
    }
}
