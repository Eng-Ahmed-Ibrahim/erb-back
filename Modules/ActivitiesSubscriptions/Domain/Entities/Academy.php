<?php

namespace Modules\ActivitiesSubscriptions\Domain\Entities;

use Modules\ActivitiesSubscriptions\Domain\ValueObjects\Percentage;

class Academy
{
    private ?int $id = null;
    private string $name;
    private bool $contracted;
    private Percentage $revenueShareInfantry;
    private Percentage $revenueShareAcademy;
    private array $workingDays;
    private string $status;

    public function __construct(
        string $name,
        bool $contracted,
        Percentage $revenueShareInfantry,
        Percentage $revenueShareAcademy,
        array $workingDays,
        string $status = 'active'
    ) {
        $this->validateWorkingDays($workingDays);
        $this->validateRevenueShare($revenueShareInfantry, $revenueShareAcademy);

        $this->name = $name;
        $this->contracted = $contracted;
        $this->revenueShareInfantry = $revenueShareInfantry;
        $this->revenueShareAcademy = $revenueShareAcademy;
        $this->workingDays = $workingDays;
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function isContracted(): bool
    {
        return $this->contracted;
    }

    public function setContracted(bool $contracted): void
    {
        $this->contracted = $contracted;
    }

    public function getRevenueShareInfantry(): Percentage
    {
        return $this->revenueShareInfantry;
    }

    public function getRevenueShareAcademy(): Percentage
    {
        return $this->revenueShareAcademy;
    }

    public function setRevenueShareInfantry(Percentage $revenueShareInfantry): void
    {
        $this->revenueShareInfantry = $revenueShareInfantry;
    }

    public function setRevenueShareAcademy(Percentage $revenueShareAcademy): void
    {
        $this->revenueShareAcademy = $revenueShareAcademy;
    }

    public function getWorkingDays(): array
    {
        return $this->workingDays;
    }

    public function setWorkingDays(array $workingDays): void
    {
        $this->validateWorkingDays($workingDays);
        $this->workingDays = $workingDays;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        if (!in_array($status, ['active', 'inactive'])) {
            throw new \InvalidArgumentException('Status must be either active or inactive');
        }
        $this->status = $status;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function activate(): void
    {
        $this->status = 'active';
    }

    public function deactivate(): void
    {
        $this->status = 'inactive';
    }

    private function validateWorkingDays(array $workingDays): void
    {
        $validDays = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        foreach ($workingDays as $day) {
            if (!in_array($day, $validDays)) {
                throw new \InvalidArgumentException("Invalid working day: {$day}");
            }
        }
    }

    private function validateRevenueShare(Percentage $infantry, Percentage $academy): void
    {
        $total = $infantry->getValue() + $academy->getValue();
        if ($total !== 100.0) {
            throw new \InvalidArgumentException('Revenue share percentages must total 100%');
        }
    }
}
