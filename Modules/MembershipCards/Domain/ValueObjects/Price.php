<?php

namespace Modules\MembershipCards\Domain\ValueObjects;

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

    public function add(Price $other): self
    {
        $this->ensureSameCurrency($other);
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Price $other): self
    {
        $this->ensureSameCurrency($other);
        $newAmount = $this->amount - $other->amount;
        
        if ($newAmount < 0) {
            throw new \InvalidArgumentException('Resulting price cannot be negative');
        }
        
        return new self($newAmount, $this->currency);
    }

    public function multiply(float $factor): self
    {
        if ($factor < 0) {
            throw new \InvalidArgumentException('Factor cannot be negative');
        }
        
        return new self($this->amount * $factor, $this->currency);
    }

    public function isZero(): bool
    {
        return $this->amount === 0.0;
    }

    public function isGreaterThan(Price $other): bool
    {
        $this->ensureSameCurrency($other);
        return $this->amount > $other->amount;
    }

    public function isLessThan(Price $other): bool
    {
        $this->ensureSameCurrency($other);
        return $this->amount < $other->amount;
    }

    public function equals(Price $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function format(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    private function ensureSameCurrency(Price $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot operate on prices with different currencies');
        }
    }

    public static function zero(string $currency = 'EGP'): self
    {
        return new self(0, $currency);
    }

    public function __toString(): string
    {
        return $this->format();
    }
}

