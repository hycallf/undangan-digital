<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    private string $token;
    private string $baseUrl;

    public function __construct()
    {
        $this->token = config('services.fonnte.token');
        $this->baseUrl = 'https://api.fonnte.com';
    }

    /**
     * Send WhatsApp message
     */
    public function sendMessage(string $target, string $message): array
    {
        try {
            if (empty($this->token)) {
                return [
                    'success' => false,
                    'message' => 'Fonnte API token tidak dikonfigurasi'
                ];
            }

            $formattedTarget = $this->formatPhoneNumber($target);

            if (!$formattedTarget) {
                return [
                    'success' => false,
                    'message' => 'Nomor telepon tidak valid: ' . $target
                ];
            }

            Log::info('Sending WhatsApp message', [
                'target' => $formattedTarget,
                'message_length' => strlen($message)
            ]);

            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post($this->baseUrl . '/send', [
                'target' => $formattedTarget,
                'message' => $message,
                'countryCode' => '62',
            ]);

            $result = $response->json();

            Log::info('Fonnte API response', [
                'status' => $response->status(),
                'response' => $result
            ]);

            if ($response->successful() && isset($result['status']) && $result['status']) {
                return [
                    'success' => true,
                    'message' => 'Pesan berhasil dikirim',
                    'data' => $result
                ];
            }

            return [
                'success' => false,
                'message' => $result['reason'] ?? 'Gagal mengirim pesan',
                'data' => $result
            ];

        } catch (\Exception $e) {
            Log::error('Fonnte service error', [
                'error' => $e->getMessage(),
                'target' => $target
            ]);

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check account balance
     */
    public function checkBalance(): array
    {
        try {
            if (empty($this->token)) {
                return [
                    'success' => false,
                    'message' => 'Fonnte API token tidak dikonfigurasi'
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post($this->baseUrl . '/validate');

            $result = $response->json();

            if ($response->successful() && isset($result['quota'])) {
                return [
                    'success' => true,
                    'balance' => $result['quota'],
                    'device' => $result['device'] ?? 'N/A',
                    'message' => 'Saldo berhasil diambil'
                ];
            }

            return [
                'success' => false,
                'message' => $result['reason'] ?? 'Gagal mengecek saldo'
            ];

        } catch (\Exception $e) {
            Log::error('Fonnte balance check error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate phone number format
     */
    public function validatePhoneNumber(string $phone): bool
    {
        if (empty($phone)) {
            return false;
        }

        // Clean the phone number
        $cleaned = preg_replace('/[^\d+]/', '', $phone);

        // Check if it's a valid Indonesian number
        if (preg_match('/^(\+62|62|0)[8][1-9]\d{7,10}$/', $cleaned)) {
            return true;
        }

        // Check for other international formats (basic validation)
        if (preg_match('/^\+\d{10,15}$/', $cleaned)) {
            return true;
        }

        return false;
    }

    /**
     * Format phone number for API
     */
    private function formatPhoneNumber(string $phone): ?string
    {
        if (!$this->validatePhoneNumber($phone)) {
            return null;
        }

        // Remove all non-digit characters except +
        $cleaned = preg_replace('/[^\d+]/', '', $phone);

        // Convert Indonesian numbers to +62 format
        if (preg_match('/^0[8]\d+/', $cleaned)) {
            $cleaned = '+62' . substr($cleaned, 1);
        } elseif (preg_match('/^[8]\d+/', $cleaned)) {
            $cleaned = '+62' . $cleaned;
        } elseif (preg_match('/^62[8]\d+/', $cleaned)) {
            $cleaned = '+' . $cleaned;
        }

        return $cleaned;
    }

    /**
     * Get formatted phone number for display
     */
    public function getFormattedPhoneNumber(string $phone): string
    {
        return $this->formatPhoneNumber($phone) ?? $phone;
    }
}
