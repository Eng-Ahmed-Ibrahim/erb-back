<?php

namespace Modules\MembershipCards\Domain\ValueObjects;

class MembershipNumber
{
    private string $value;

    public function __construct(string $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(MembershipNumber $other): bool
    {
        return $this->value === $other->value;
    }

    private function validate(string $value): void
    {
        if (empty(trim($value))) {
            throw new \InvalidArgumentException('Membership number cannot be empty');
        }

        if (strlen($value) > 50) {
            throw new \InvalidArgumentException('Membership number cannot exceed 50 characters');
        }
    }

    public static function generate(string $prefix = 'MC'): self
    {
        $timestamp = now()->format('Ymd');
        $random = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        return new self($prefix . '-' . $timestamp . '-' . $random);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

