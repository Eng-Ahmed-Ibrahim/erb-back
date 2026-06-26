<?php

namespace Modules\ActivitiesSubscriptions\Domain\ValueObjects;

class Percentage
{
    private float $value;

    public function __construct(float $value)
    {
        if ($value < 0 || $value > 100) {
            throw new \InvalidArgumentException('Percentage must be between 0 and 100');
        }
        
        $this->value = $value;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getDecimal(): float
    {
        return $this->value / 100;
    }

    public function equals(Percentage $other): bool
    {
        return $this->value === $other->value;
    }

    public function add(Percentage $other): Percentage
    {
        $newValue = $this->value + $other->value;
        if ($newValue > 100) {
            throw new \InvalidArgumentException('Sum of percentages cannot exceed 100%');
        }
        
        return new Percentage($newValue);
    }

    public function calculateAmount(float $baseAmount): float
    {
        return $baseAmount * $this->getDecimal();
    }

    public function __toString(): string
    {
        return number_format($this->value, 2) . '%';
    }
}
