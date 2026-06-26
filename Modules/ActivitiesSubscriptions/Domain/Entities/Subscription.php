<?php

namespace Modules\ActivitiesSubscriptions\Domain\Entities;

use Carbon\Carbon;
use Modules\ActivitiesSubscriptions\Domain\ValueObjects\QRCode;
use Modules\ActivitiesSubscriptions\Domain\ValueObjects\Duration;

class Subscription
{
    private ?int $id = null;
    private int $subscriberId;
    private int $offerId;
    private int $academyId;
    private string $createdBy;
    private Duration $duration;
    private array $chosenDays;
    private int|float $remainingClasses;
    private int|float $remainingHours;
    private QRCode $qrCode;
    private string $status;

    public function __construct(
        int $subscriberId,
        int $offerId,
        int $academyId,
        string $createdBy,
        Duration $duration,
        array $chosenDays,
        int|float $initialClasses,
        int|float $initialHours,
        QRCode $qrCode,
        string $status = 'active'
    ) {
        $this->validateStatus($status);
        $this->validateChosenDays($chosenDays);
        
        $this->subscriberId = $subscriberId;
        $this->offerId = $offerId;
        $this->academyId = $academyId;
        $this->createdBy = $createdBy;
        $this->duration = $duration;
        $this->chosenDays = $chosenDays;
        $this->remainingClasses = $initialClasses;
        $this->remainingHours = $initialHours;
        $this->qrCode = $qrCode;
        $this->status = $status;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getSubscriberId(): int
    {
        return $this->subscriberId;
    }

    public function getOfferId(): int
    {
        return $this->offerId;
    }

    public function getAcademyId(): int
    {
        return $this->academyId;
    }

    public function getCreatedBy(): string
    {
        return $this->createdBy;
    }

    public function getDuration(): Duration
    {
        return $this->duration;
    }

    public function getChosenDays(): array
    {
        return $this->chosenDays;
    }

    public function getRemainingClasses(): int|float
    {
        return $this->remainingClasses;
    }

    public function getRemainingHours(): int|float
    {
        return $this->remainingHours;
    }

    public function getQrCode(): QRCode
    {
        return $this->qrCode;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->duration->isActive();
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || $this->duration->isExpired();
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function hasRemainingBalance(): bool
    {
        return $this->remainingClasses > 0 || $this->remainingHours > 0;
    }

    public function canCheckIn(string $dayOfWeek): bool
    {
        return $this->isActive() && 
               $this->hasRemainingBalance() && 
               in_array($dayOfWeek, $this->chosenDays);
    }

    public function checkIn(): void
    {
        if (!$this->isActive()) {
            throw new \InvalidArgumentException('الاشتراك غير مفعل للتسجيل');
        }
        
        if (!$this->hasRemainingBalance()) {
            throw new \InvalidArgumentException('لا توجد حصص أو ساعات متبقية للتسجيل');
        }
        
        if ($this->remainingClasses > 0) {
            $this->remainingClasses--;
        }
        
        if ($this->remainingHours > 0) {
            $this->remainingHours--;
        }
        
        // Auto-expire if no remaining balance
        if (!$this->hasRemainingBalance()) {
            $this->status = 'expired';
        }
    }

    public function cancel(): void
    {
        $this->status = 'cancelled';
    }

    public function expire(): void
    {
        $this->status = 'expired';
    }

    public function getRemainingValue(): int|float
    {
        return $this->remainingClasses > 0 ? $this->remainingClasses : $this->remainingHours;
    }

    public function extendEndDate(Carbon $newEndDate): void
    {
        $this->duration->extendTo($newEndDate);
    }

    public function updateStartDate(Carbon $newStartDate): void
    {
        $this->duration->updateStartDate($newStartDate);
    }
    private function validateStatus(string $status): void
    {
        $validStatuses = ['active', 'expired', 'cancelled'];
        
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid subscription status: {$status}");
        }
    }

    private function validateChosenDays(array $chosenDays): void
    {
        $validDays = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        
        foreach ($chosenDays as $day) {
            if (!in_array($day, $validDays)) {
                throw new \InvalidArgumentException("Invalid chosen day: {$day}");
            }
        }
    }
}
