<?php

namespace Modules\ActivitiesSubscriptions\Domain\Services;

use Modules\ActivitiesSubscriptions\Domain\ValueObjects\QRCode;

class QRCodeService
{
    public function generateQRCode(int $subscriptionId): QRCode
    {
        return QRCode::generate((string) $subscriptionId);
    }

    public function validateQRCode(string $qrCodeValue): bool
    {
        try {
            $decrypted = QRCode::decrypt($qrCodeValue);
            return $decrypted !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function extractSubscriptionId(string $qrCodeValue): ?int
    {
        try {
            $decrypted = QRCode::decrypt($qrCodeValue);
            if ($decrypted === null) {
                return null;
            }
            
            // Additional logic to extract subscription ID from decrypted data
            // This is simplified - in production, you'd have proper decryption
            return (int) $decrypted;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function isQRCodeValid(string $qrCodeValue): bool
    {
        return $this->validateQRCode($qrCodeValue) && $this->extractSubscriptionId($qrCodeValue) !== null;
    }
}
