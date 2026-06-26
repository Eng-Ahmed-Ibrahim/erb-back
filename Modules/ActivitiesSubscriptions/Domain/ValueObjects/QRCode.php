<?php

namespace Modules\ActivitiesSubscriptions\Domain\ValueObjects;

class QRCode
{
    private string $value;

    public function __construct(string $value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('QR code value cannot be empty');
        }
        
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(QRCode $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function generate(string $subscriptionId): QRCode
    {
        // Generate encrypted QR code based on subscription ID
        $encrypted = base64_encode(hash('sha256', $subscriptionId . config('app.key'), true));
        return new QRCode($encrypted);
    }

    public static function decrypt(string $qrCodeValue): ?string
    {
        try {
            // This is a simplified decryption - in production, use proper encryption
            $decoded = base64_decode($qrCodeValue);
            // Additional decryption logic would go here
            return $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }
}
