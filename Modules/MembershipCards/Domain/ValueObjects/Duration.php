<?php

namespace Modules\MembershipCards\Domain\ValueObjects;

use Carbon\Carbon;

class Duration
{
    private Carbon $startDate;
    private Carbon $endDate;

    public function __construct(Carbon $startDate, Carbon $endDate)
    {
        if ($endDate->isBefore($startDate)) {
            throw new \InvalidArgumentException('End date cannot be before start date');
        }
        
        $this->startDate = $startDate->copy();
        $this->endDate = $endDate->copy();
    }

    public function getStartDate(): Carbon
    {
        return $this->startDate->copy();
    }

    public function getEndDate(): Carbon
    {
        return $this->endDate->copy();
    }

    public function getDaysCount(): int
    {
        return $this->startDate->diffInDays($this->endDate) + 1;
    }

    public function getMonthsCount(): int
    {
        return $this->startDate->diffInMonths($this->endDate);
    }

    public function contains(Carbon $date): bool
    {
        return $date->between($this->startDate, $this->endDate);
    }

    public function isActive(): bool
    {
        $now = Carbon::now();
        return $this->contains($now);
    }

    public function isExpired(): bool
    {
        return Carbon::now()->isAfter($this->endDate);
    }

    public function isFuture(): bool
    {
        return Carbon::now()->isBefore($this->startDate);
    }

    public function extendTo(Carbon $newEndDate): void
    {
        if ($newEndDate->isBefore($this->startDate)) {
            throw new \InvalidArgumentException('End date cannot be before start date');
        }

        if ($newEndDate->isBefore($this->endDate)) {
            throw new \InvalidArgumentException('New end date must be after current end date');
        }

        $this->endDate = $newEndDate->copy();
    }

    public function equals(Duration $other): bool
    {
        return $this->startDate->equalTo($other->startDate) && 
               $this->endDate->equalTo($other->endDate);
    }

    public function overlaps(Duration $other): bool
    {
        return $this->startDate->lte($other->endDate) && $this->endDate->gte($other->startDate);
    }

    public static function fromYear(int $year): self
    {
        return new self(
            Carbon::create($year, 1, 1)->startOfDay(),
            Carbon::create($year, 12, 31)->endOfDay()
        );
    }

    public static function forMonths(int $months, ?Carbon $startDate = null): self
    {
        $start = $startDate ?? Carbon::now();
        return new self(
            $start->copy()->startOfDay(),
            $start->copy()->addMonths($months)->endOfDay()
        );
    }

    public function __toString(): string
    {
        return $this->startDate->format('Y-m-d') . ' to ' . $this->endDate->format('Y-m-d');
    }
}

