<?php

namespace Modules\ActivitiesSubscriptions\Application\DTOs;


class CreateOfferDTO
{
    public function __construct(
        public readonly int $academyId,
        public readonly string $name,
        public readonly ?int $numClasses,
        public readonly ?int $numHours,
        public readonly int $durationDays,
        public readonly array $availableDays,
        public readonly float $priceInfantry,
        public readonly float $priceCivilian,
        public readonly float $priceOther,
        public readonly bool $active = true
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            academyId: $data['academy_id'],
            name: $data['name'],
            numClasses: $data['num_classes'] ?? null,
            numHours: $data['num_hours'] ?? null,
            durationDays: $data['duration_days'],
            availableDays: $data['available_days'],
            priceInfantry: $data['price_infantry'],
            priceCivilian: $data['price_civilian'],
            priceOther: $data['price_other'],
            active: $data['active'] ?? true
        );
    }

    public function toArray(): array
    {
        return [
            'academy_id' => $this->academyId,
            'name' => $this->name,
            'num_classes' => $this->numClasses,
            'num_hours' => $this->numHours,
            'duration_days' => $this->durationDays,
            'available_days' => $this->availableDays,
            'price_infantry' => $this->priceInfantry,
            'price_civilian' => $this->priceCivilian,
            'price_other' => $this->priceOther,
            'active' => $this->active,
        ];
    }
}
