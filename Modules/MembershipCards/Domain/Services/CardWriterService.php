<?php

namespace Modules\MembershipCards\Domain\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class CardWriterService
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;

    public function __construct()
    {
        $config = Config::get('services.card_writer');
        $this->baseUrl = rtrim($config['url'], '/');
        $this->apiKey = $config['api_key'];
        $this->timeout = $config['timeout'] ?? 30;
    }

    /**
     * Write data to NFC card via client machine API
     *
     * @param string $cardToken Card UID/token
     * @param string $dataHex Hex string data to write (16 bytes = 32 hex chars)
     * @param int $block Block number (default: 4)
     * @return array Response from the API
     * @throws \Exception
     */
    public function writeCard(string $cardToken, string $dataHex, int $block = 4): array
    {
        if (empty($this->baseUrl)) {
            throw new \Exception('Card writer URL is not configured');
        }

        if (empty($this->apiKey)) {
            throw new \Exception('Card writer API key is not configured');
        }

        // Validate hex data length (16 bytes = 32 hex characters)
        $cleanHex = str_replace([' ', ':', '-'], '', $dataHex);
        if (strlen($cleanHex) !== 32) {
            throw new \Exception('Data must be exactly 16 bytes (32 hex characters)');
        }

        if (!preg_match('/^[0-9A-Fa-f]+$/', $cleanHex)) {
            throw new \Exception('Data must be a valid hexadecimal string');
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$this->baseUrl}/api/cards/write", [
                    'card_token' => $cardToken,
                    'data' => strtoupper($cleanHex),
                    'block' => $block,
                ]);

            if ($response->failed()) {
                $errorMessage = $response->json()['message'] ?? 'Unknown error';
                Log::error('Card writer API error', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                throw new \Exception("Card writer API error: {$errorMessage}");
            }

            $data = $response->json();

            if (!isset($data['status']) || $data['status'] !== 'success') {
                throw new \Exception($data['message'] ?? 'Card writing failed');
            }

            return $data;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Card writer connection error', [
                'url' => $this->baseUrl,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Cannot connect to card writer. Please ensure the device is connected and accessible.');
        } catch (\Exception $e) {
            Log::error('Card writer error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get card serial ID (UID) from NFC reader
     *
     * @param string|null $serialId Optional serial ID to query
     * @return array Response from the API
     * @throws \Exception
     */
    public function getSerialId(?string $serialId = null): array
    {
        if (empty($this->baseUrl)) {
            throw new \Exception('Card writer URL is not configured');
        }

        if (empty($this->apiKey)) {
            throw new \Exception('Card writer API key is not configured');
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$this->baseUrl}/api/cards/serial-id", [
                    'serial_id' => $serialId ?? '',
                ]);

            if ($response->failed()) {
                $errorMessage = $response->json()['message'] ?? 'Unknown error';
                Log::error('Card writer serial-id API error', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                throw new \Exception("Card writer API error: {$errorMessage}");
            }

            return $response->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Card writer connection error', [
                'url' => $this->baseUrl,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Cannot connect to card writer. Please ensure the device is connected and accessible.');
        } catch (\Exception $e) {
            Log::error('Card writer error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if card writer service is available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return !empty($this->baseUrl) && !empty($this->apiKey);
    }
}

