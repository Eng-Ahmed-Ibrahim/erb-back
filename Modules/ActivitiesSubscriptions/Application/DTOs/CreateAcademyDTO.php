<?php

namespace Modules\ActivitiesSubscriptions\Application\DTOs;

class CreateAcademyDTO
{
    public function __construct(
        public readonly string $name,
        public readonly bool $contracted,
        public readonly float $revenueShareInfantry,
        public readonly float $revenueShareAcademy,
        public readonly array $workingDays,
        public readonly string $status = 'active'
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            contracted: $data['contracted'] ?? false,
            revenueShareInfantry: $data['revenue_share_infantry'],
            revenueShareAcademy: $data['revenue_share_academy'],
            workingDays: $data['working_days'],
            status: $data['status'] ?? 'active'
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'contracted' => $this->contracted,
            'revenue_share_infantry' => $this->revenueShareInfantry,
            'revenue_share_academy' => $this->revenueShareAcademy,
            'working_days' => $this->workingDays,
            'status' => $this->status,
        ];
    }
}
