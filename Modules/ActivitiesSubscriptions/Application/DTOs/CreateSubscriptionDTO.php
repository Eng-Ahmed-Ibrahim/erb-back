<?php

namespace Modules\ActivitiesSubscriptions\Application\DTOs;

use Carbon\Carbon;

class CreateSubscriptionDTO
{
    public function __construct(
        public readonly int $subscriberId,
        public readonly int $offerId,
        public readonly int $academyId,
        public readonly string $createdBy,
        public readonly Carbon $startDate,
        public readonly Carbon $endDate,
        public readonly array $chosenDays,
        public readonly int|float $initialClasses,
        public readonly int|float $initialHours,
        public readonly string $status = 'active'
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            subscriberId: $data['subscriber_id'],
            offerId: $data['offer_id'],
            academyId: $data['academy_id'],
            createdBy: $data['created_by'],
            startDate: Carbon::parse($data['start_date']),
            endDate: Carbon::parse($data['end_date']),
            chosenDays: $data['chosen_days'],
            initialClasses: $data['initial_classes'] ?? 0,
            initialHours: $data['initial_hours'] ?? 0,
            status: $data['status'] ?? 'active'
        );
    }

    public function toArray(): array
    {
        return [
            'subscriber_id' => $this->subscriberId,
            'offer_id' => $this->offerId,
            'academy_id' => $this->academyId,
            'created_by' => $this->createdBy,
            'start_date' => $this->startDate->format('Y-m-d'),
            'end_date' => $this->endDate->format('Y-m-d'),
            'chosen_days' => $this->chosenDays,
            'initial_classes' => $this->initialClasses,
            'initial_hours' => $this->initialHours,
            'status' => $this->status,
        ];
    }
}
