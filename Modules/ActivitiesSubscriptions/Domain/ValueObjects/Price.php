<?php

namespace Modules\ActivitiesSubscriptions\Domain\ValueObjects;

class Price
{
    private float $amount;
    private string $currency;

    public function __construct(float $amount, string $currency = 'EGP')
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Price amount cannot be negative');
        }
        
        $this->amount = $amount;
        $this->currency = $currency;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function equals(Price $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function add(Price $other): Price
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot add prices with different currencies');
        }
        
        return new Price($this->amount + $other->amount, $this->currency);
    }

    public function multiply(float $factor): Price
    {
        return new Price($this->amount * $factor, $this->currency);
    }

    public function __toString(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }
}
