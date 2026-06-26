<?php

namespace Modules\ActivitiesSubscriptions\Domain\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;

class QRCodeImageService
{
    private string $storagePath = 'qr-codes';
    
    public function generateQRCodeImage(string $data, int $subscriptionId): array
    {
        // Generate a unique filename for the QR code
        $filename = "subscription_{$subscriptionId}_" . Str::random(10) . '.svg';
        
        try {
            // Clean and prepare data for QR code generation
            $cleanData = $this->prepareDataForQRCode($data);
            
            // Generate QR code as SVG using SimpleSoftwareIO library
            $qrCodeSvg = QrCode::format('svg')
                ->size(200)
                ->margin(1)
                ->errorCorrection('M')
                ->generate($cleanData);
            
        } catch (\Exception $e) {
            // If QR generation fails, create a simple fallback
            $cleanData = "Subscription ID: {$subscriptionId}\nGenerated: " . now()->format('Y-m-d H:i:s');
            $qrCodeSvg = QrCode::format('svg')
                ->size(200)
                ->margin(1)
                ->errorCorrection('M')
                ->generate($cleanData);
        }
        
        // Store the QR code SVG
        $filePath = $this->storagePath . '/' . $filename;
        
        // Create directory if it doesn't exist
        try {
            Storage::makeDirectory($this->storagePath);
        } catch (\Exception $e) {
            // If directory creation fails, try to create it manually
            $fullPath = storage_path('app/' . $this->storagePath);
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
        }
        
        // Store the QR code SVG
        try {
            Storage::put($filePath, $qrCodeSvg);
        } catch (\Exception $e) {
            // If storage fails, try to write directly to file system
            $fullPath = storage_path('app/' . $filePath);
            file_put_contents($fullPath, $qrCodeSvg);
        }
        
        return [
            'filename' => $filename,
            'file_path' => $filePath,
            'url' => Storage::url($filePath),
            'data' => $data,
            'subscription_id' => $subscriptionId,
            'created_at' => now()->toISOString(),
            'svg_data' => $qrCodeSvg, // SVG data for direct display
            'image_data' => base64_encode($qrCodeSvg) // Base64 encoded SVG for compatibility
        ];
    }
    
    public function generateQRCodeForSubscription(int $subscriptionId, array $subscriptionData): array
    {
        // Generate secure QR code data with HMAC token
        $secureQRData = $this->generateSecureQRCodeData($subscriptionId, $subscriptionData);

        $qrCodeInfo = $this->generateQRCodeImage($secureQRData, $subscriptionId);
        
        // Also generate barcode for the same data
        $barcodeInfo = $this->generateBarcodeForSubscription($subscriptionId, $subscriptionData);
        
        // Merge QR code and barcode info
        return array_merge($qrCodeInfo, [
            'barcode' => $barcodeInfo
        ]);
    }
    
    public function generateBarcodeForSubscription(int $subscriptionId, array $subscriptionData): array
    {
        $barcodeData = $this->generateSecureBarcodeData($subscriptionId, $subscriptionData);
        
        try {
            $generator = new BarcodeGeneratorSVG();
            $barcodeSvg = $generator->getBarcode($barcodeData, $generator::TYPE_CODE_39, 6, 150);
            
            $filename = "subscription_{$subscriptionId}_barcode_" . Str::random(10) . '.svg';
            $filePath = $this->storagePath . '/' . $filename;
            
            try {
                Storage::makeDirectory($this->storagePath);
            } catch (\Exception $e) {
                $fullPath = storage_path('app/' . $this->storagePath);
                if (!is_dir($fullPath)) {
                    mkdir($fullPath, 0755, true);
                }
            }
            
            try {
                Storage::put($filePath, $barcodeSvg);
            } catch (\Exception $e) {
                $fullPath = storage_path('app/' . $filePath);
                file_put_contents($fullPath, $barcodeSvg);
            }
            
            return [
                'filename' => $filename,
                'file_path' => $filePath,
                'url' => Storage::url($filePath),
                'data' => $barcodeData,
                'subscription_id' => $subscriptionId,
                'created_at' => now()->toISOString(),
                'svg_data' => $barcodeSvg,
                'image_data' => base64_encode($barcodeSvg)
            ];
            
        } catch (\Exception $e) {
            // Fallback barcode generation
            return [
                'filename' => "subscription_{$subscriptionId}_barcode_fallback.svg",
                'file_path' => null,
                'url' => null,
                'data' => $barcodeData,
                'subscription_id' => $subscriptionId,
                'created_at' => now()->toISOString(),
                'svg_data' => $this->createFallbackBarcode($barcodeData),
                'image_data' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate barcode data with subscription ID and academy ID
     */
    private function generateSecureBarcodeData(int $subscriptionId, array $subscriptionData): string
    {
        $academyId = $subscriptionData['academy_id'] ?? 0;
        return $subscriptionId;
        // . '.' . $academyId;
    }

    /**
     * Generate secure QR code data with HMAC token
     */
    private function generateSecureQRCodeData(int $subscriptionId, array $subscriptionData): string
    {
        // Use Laravel's app key as secret
        $secretKey = config('app.key');

        // Use subscription end date as expiration timestamp
        $endDateTimestamp = isset($subscriptionData['end_date'])
            ? strtotime($subscriptionData['end_date'])
            : (now()->timestamp + (30 * 24 * 60 * 60)); // 30 days fallback

        // Create QR code data with subscription information
        $qrData = [
            'subscription_id' => $subscriptionId,
            'subscriber_name' => $subscriptionData['subscriber_name'] ?? 'Unknown',
            'academy_name' => $subscriptionData['academy_name'] ?? 'Unknown',
            'offer_name' => $subscriptionData['offer_name'] ?? 'Unknown',
            'start_date' => $subscriptionData['start_date'] ?? null,
            'end_date' => $subscriptionData['end_date'] ?? null,
            'remaining_classes' => $subscriptionData['remaining_classes'] ?? 0,
            'remaining_hours' => $subscriptionData['remaining_hours'] ?? 0,
            'status' => $subscriptionData['status'] ?? 'active',
            'expires_at' => $endDateTimestamp,
            'generated_at' => now()->timestamp
        ];

        // Create payload for HMAC signature
        $payload = implode('|', [
            $subscriptionId,
            $endDateTimestamp,
            $subscriptionData['subscriber_name'] ?? 'Unknown',
            $subscriptionData['academy_id'] ?? 0,
            $subscriptionData['offer_id'] ?? 0,
            'QR_TOKEN' // Identifier to distinguish from barcode tokens
        ]);

        $signature = hash_hmac('sha256', $payload, $secretKey);

        $qrData['security_token'] = substr($signature, 0, 16);
        $qrData['token_type'] = 'QR_HMAC';

        $qrString = json_encode($qrData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Ensure the string is properly encoded for QR code generation
        return mb_convert_encoding($qrString, 'UTF-8', 'UTF-8');
    }

    /**
     * Verify secure QR code data
     */
    public function verifySecureQRCodeData(string $qrData): array
    {
        try {
            $data = json_decode($qrData, true);

            if (!$data || !isset($data['subscription_id'], $data['security_token'], $data['expires_at'])) {
                return ['valid' => false, 'error' => 'Invalid QR code format'];
            }

            $subscriptionId = $data['subscription_id'];
            $providedToken = $data['security_token'];
            $expiresAt = $data['expires_at'];

            // Check if QR code is expired
            if (time() > $expiresAt) {
                return [
                    'valid' => false,
                    'error' => 'QR code expired on ' . date('Y-m-d', $expiresAt)
                ];
            }

            // Get subscription data to verify signature
            $subscription = app(\Modules\ActivitiesSubscriptions\Domain\Repositories\SubscriptionRepositoryInterface::class)
                ->findById((int) $subscriptionId);

            if (!$subscription) {
                return ['valid' => false, 'error' => 'Subscription not found'];
            }

            // Get related data for verification
            $subscriber = app(\Modules\ActivitiesSubscriptions\Domain\Repositories\SubscriberRepositoryInterface::class)
                ->findById($subscription->getSubscriberId());

            // Recreate the payload
            $payload = implode('|', [
                $subscriptionId,
                $expiresAt,
                $subscriber ? $subscriber->getFullName() : 'Unknown',
                $subscription->getAcademyId(),
                $subscription->getOfferId(),
                'QR_TOKEN'
            ]);

            $secretKey = config('app.key');
            $expectedToken = substr(hash_hmac('sha256', $payload, $secretKey), 0, 16);

            if (!hash_equals($expectedToken, $providedToken)) {
                return ['valid' => false, 'error' => 'Invalid security token - QR code may be forged'];
            }

            return [
                'valid' => true,
                'subscription_id' => (int) $subscriptionId,
                'expires_at' => $expiresAt,
                'expires_date' => date('Y-m-d', $expiresAt),
                'days_remaining' => max(0, ceil(($expiresAt - time()) / (24 * 60 * 60))),
                'subscription' => $subscription,
                'qr_data' => $data
            ];

        } catch (\Exception $e) {
            return ['valid' => false, 'error' => 'QR code verification failed: ' . $e->getMessage()];
        }
    }

    /**
     * Verify barcode data with subscription ID and academy ID
     */
    public function verifySecureBarcodeData(string $barcodeData): array
    {
        $cleanBarcode = trim($barcodeData);

        $parts = explode('.', $cleanBarcode);

        if (count($parts) !== 2) {
            return ['valid' => false, 'error' => 'Invalid barcode format - must be SUBSCRIPTION_ID.ACADEMY_ID'];
        }

        $subscriptionIdStr = $parts[0];
        $academyIdStr = $parts[1];

        $subscriptionIdStr = str_replace('.', '', $subscriptionIdStr);
        $academyIdStr = str_replace('.', '', $academyIdStr);

        if (!is_numeric($subscriptionIdStr) || !is_numeric($academyIdStr)) {
            return ['valid' => false, 'error' => 'Invalid barcode format - both parts must be numeric'];
        }

        $subscriptionId = (int) $subscriptionIdStr;
        $academyId = (int) $academyIdStr;

        try {
            $subscription = app(\Modules\ActivitiesSubscriptions\Domain\Repositories\SubscriptionRepositoryInterface::class)
                ->findById($subscriptionId);

            if (!$subscription) {
                return ['valid' => false, 'error' => 'Subscription not found'];
            }

            if ($subscription->getAcademyId() !== $academyId) {
                return ['valid' => false, 'error' => 'Academy ID mismatch - barcode may be for different academy'];
            }

            if ($subscription->getStatus() !== 'active') {
                return ['valid' => false, 'error' => 'Subscription is not active'];
            }

            $endDate = $subscription->getDuration()->getEndDate();
            if ($endDate->isPast()) {
                return [
                    'valid' => false,
                    'error' => 'Subscription expired on ' . $endDate->format('Y-m-d')
                ];
            }

            return [
                'valid' => true,
                'subscription_id' => $subscriptionId,
                'academy_id' => $academyId,
                'offer_id' => $subscription->getOfferId(),
                'end_date' => $endDate->format('Y-m-d'),
                'days_remaining' => max(0, $endDate->diffInDays(now())),
                'subscription' => $subscription
            ];

        } catch (\Exception $e) {
            return ['valid' => false, 'error' => 'Verification failed: ' . $e->getMessage()];
        }
    }

    private function createFallbackBarcode(string $data): string
    {
        // Create a simple SVG barcode representation
        $width = strlen($data) * 8;
        $height = 50;
        
        $svg = '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">';
        $svg .= '<rect width="' . $width . '" height="' . $height . '" fill="white" stroke="black" stroke-width="1"/>';
        $svg .= '<text x="' . ($width / 2) . '" y="' . ($height / 2 + 5) . '" text-anchor="middle" font-family="monospace" font-size="12" fill="black">' . htmlspecialchars($data) . '</text>';
        $svg .= '</svg>';
        
        return $svg;
    }
    
    private function prepareDataForQRCode(string $data): string
    {
        $cleanData = $data;
        
        $arabicReplacements = [
            'أ' => 'A', 'ب' => 'B', 'ت' => 'T', 'ث' => 'TH', 'ج' => 'J', 'ح' => 'H',
            'خ' => 'KH', 'د' => 'D', 'ذ' => 'DH', 'ر' => 'R', 'ز' => 'Z', 'س' => 'S',
            'ش' => 'SH', 'ص' => 'S', 'ض' => 'D', 'ط' => 'T', 'ظ' => 'Z', 'ع' => 'A',
            'غ' => 'GH', 'ف' => 'F', 'ق' => 'Q', 'ك' => 'K', 'ل' => 'L', 'م' => 'M',
            'ن' => 'N', 'ه' => 'H', 'و' => 'W', 'ي' => 'Y', 'ة' => 'H', 'ء' => 'A',
            'آ' => 'A', 'إ' => 'I', 'ا' => 'A', 'ى' => 'A'
        ];
        
        // Apply replacements
        foreach ($arabicReplacements as $arabic => $english) {
            $cleanData = str_replace($arabic, $english, $cleanData);
        }
        
        // Remove any remaining non-ASCII characters
        $cleanData = preg_replace('/[^\x20-\x7E]/', '', $cleanData);
        
        // Ensure the string is not empty
        if (empty(trim($cleanData))) {
            $cleanData = "QR Code Data - " . now()->format('Y-m-d H:i:s');
        }
        
        return $cleanData;
    }
    
    private function createQRCodeData(string $data): string
    {
        // This is a placeholder for actual QR code generation
        // In production, you would use a proper QR code library
        
        // For now, create a simple text representation
        $qrCodeText = "QR CODE DATA:\n" . $data . "\n\n";
        $qrCodeText .= "Generated at: " . now()->format('Y-m-d H:i:s') . "\n";
        $qrCodeText .= "This is a placeholder QR code. In production, this would be an actual QR code image.";
        
        return $qrCodeText;
    }
    
    public function getQRCodeUrl(string $filename): ?string
    {
        $filePath = $this->storagePath . '/' . $filename;
        
        if (Storage::exists($filePath)) {
            return Storage::url($filePath);
        }
        
        return null;
    }
    
    public function deleteQRCode(string $filename): bool
    {
        $filePath = $this->storagePath . '/' . $filename;
        
        if (Storage::exists($filePath)) {
            return Storage::delete($filePath);
        }
        
        return false;
    }
    
    public function generateQRCodeWithSimpleLibrary(string $data, int $subscriptionId): array
    {
        // Use SimpleSoftwareIO/SimpleQRCode library with SVG format
        try {
            // Generate QR code as SVG
            $qrCodeSvg = QrCode::format('svg')
                ->size(200)
                ->margin(1)
                ->errorCorrection('M')
                ->generate($data);
            
            $filename = "subscription_{$subscriptionId}_" . Str::random(10) . '.svg';
            $filePath = $this->storagePath . '/' . $filename;
            
            // Create directory if it doesn't exist
            Storage::makeDirectory($this->storagePath);
            
            // Store the QR code SVG
            Storage::put($filePath, $qrCodeSvg);
            
            return [
                'filename' => $filename,
                'file_path' => $filePath,
                'url' => Storage::url($filePath),
                'data' => $data,
                'subscription_id' => $subscriptionId,
                'created_at' => now()->toISOString(),
                'svg_data' => $qrCodeSvg,
                'image_data' => base64_encode($qrCodeSvg)
            ];
            
        } catch (\Exception $e) {
            // Fallback to the main method
            return $this->generateQRCodeImage($data, $subscriptionId);
        }
    }
}
