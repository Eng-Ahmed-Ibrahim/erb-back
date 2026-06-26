<?php

namespace Modules\MembershipCards\Domain\ValueObjects;

class CardUID
{
    private string $value;

    public function __construct(string $value)
    {
        $this->validate($value);
        $this->value = strtoupper($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(CardUID $other): bool
    {
        return $this->value === $other->value;
    }

    private function validate(string $value): void
    {
        if (empty(trim($value))) {
            throw new \InvalidArgumentException('Card UID cannot be empty');
        }

        // MIFARE UIDs are typically 4, 7, or 10 bytes (8, 14, or 20 hex characters)
        $cleanValue = str_replace([':', '-', ' '], '', $value);
        
        if (!preg_match('/^[0-9A-Fa-f]+$/', $cleanValue)) {
            throw new \InvalidArgumentException('Card UID must be a valid hexadecimal string');
        }

        $validLengths = [8, 14, 20]; // 4, 7, or 10 bytes
        if (!in_array(strlen($cleanValue), $validLengths)) {
            throw new \InvalidArgumentException('Card UID must be 4, 7, or 10 bytes (8, 14, or 20 hex characters)');
        }
    }

    public function getFormatted(): string
    {
        // Format as colon-separated pairs: AA:BB:CC:DD
        return implode(':', str_split($this->value, 2));
    }

    public function getBytes(): array
    {
        return array_map('hexdec', str_split($this->value, 2));
    }

    public static function fromBytes(array $bytes): self
    {
        $hex = implode('', array_map(fn($b) => str_pad(dechex($b), 2, '0', STR_PAD_LEFT), $bytes));
        return new self($hex);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

