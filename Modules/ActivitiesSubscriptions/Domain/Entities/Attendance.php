<?php

namespace Modules\ActivitiesSubscriptions\Domain\Entities;

use Carbon\Carbon;

class Attendance
{
    private ?int $id = null;
    private int $subscriptionId;
    private Carbon $checkInDate;
    private string $dayOfWeek;
    private int $deducted;

    public function __construct(
        int $subscriptionId,
        Carbon $checkInDate,
        string $dayOfWeek,
        int $deducted = 1
    ) {
        $this->validateDayOfWeek($dayOfWeek);
        $this->validateDeducted($deducted);
        
        $this->subscriptionId = $subscriptionId;
        $this->checkInDate = $checkInDate->copy();
        $this->dayOfWeek = $dayOfWeek;
        $this->deducted = $deducted;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getSubscriptionId(): int
    {
        return $this->subscriptionId;
    }

    public function getCheckInDate(): Carbon
    {
        return $this->checkInDate->copy();
    }

    public function getDayOfWeek(): string
    {
        return $this->dayOfWeek;
    }

    public function getDeducted(): int
    {
        return $this->deducted;
    }

    public function getFormattedCheckInDate(): string
    {
        return $this->checkInDate->format('Y-m-d H:i:s');
    }

    public function isToday(): bool
    {
        return $this->checkInDate->isToday();
    }

    public function isThisWeek(): bool
    {
        return $this->checkInDate->isCurrentWeek();
    }

    public function isThisMonth(): bool
    {
        return $this->checkInDate->isCurrentMonth();
    }

    private function validateDayOfWeek(string $dayOfWeek): void
    {
        $validDays =['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        
        if (!in_array($dayOfWeek, $validDays)) {
            throw new \InvalidArgumentException("Invalid day of week: {$dayOfWeek}");
        }
    }

    private function validateDeducted(int $deducted): void
    {
        if ($deducted <= 0) {
            throw new \InvalidArgumentException('Deducted amount must be positive');
        }
    }
}
