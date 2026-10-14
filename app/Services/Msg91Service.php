<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Msg91Service
{
    protected string $baseUrl = 'https://control.msg91.com/api/v5';
    protected ?string $authKey;
    protected ?string $templateId;
    protected ?string $senderId;
    protected int $defaultExpiry;
    protected int $defaultLength;

    public function __construct(?string $authKey = null, ?string $templateId = null)
    {
        // Read from arguments or tenant Setting first, fall back to config / env
        $this->authKey = $authKey ?? Setting::get('msg91_auth_key', config('services.msg91.auth_key', env('MSG91_AUTH_KEY', '')));
        $this->templateId = $templateId ?? Setting::get('msg91_template_id', config('services.msg91.template_id', env('MSG91_TEMPLATE_ID', '')));
        $this->senderId = Setting::get('msg91_dlt_sender_id', env('MSG91_SENDER_ID', ''));
        $this->defaultExpiry = (int) Setting::get('msg91_otp_expiry', env('MSG91_OTP_EXPIRY', 10));
        $this->defaultLength = (int) Setting::get('msg91_otp_length', env('MSG91_OTP_LENGTH', 4));
    }

    /**
     * Check if MSG91 is configured
     */
    public function isConfigured(): bool
    {
        $key = $this->authKey ?: Setting::get('msg91_auth_key', config('services.msg91.auth_key', env('MSG91_AUTH_KEY', '')));
        return !empty($key);
    }

    /**
     * Format Indian/International phone number for MSG91
     * Strips non-digits, prepends 91 if standard 10-digit Indian mobile.
     */
    public function formatPhone(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($clean) === 11 && str_starts_with($clean, '0')) {
            $clean = substr($clean, 1);
        }
        if (strlen($clean) === 10) {
            return '91' . $clean;
        }
        return $clean;
    }

    /**
     * Send OTP via MSG91 API
     *
     * @param string $mobile Customer phone number
     * @param string|null $otp Optional custom OTP code (if omitted, MSG91 generates one)
     * @param int|null $expiryMinutes OTP expiry time in minutes (default from settings)
     * @param int|null $otpLength Length of OTP (default from settings)
     * @return array
     */
    public function sendOtp(string $mobile, ?string $otp = null, ?int $expiryMinutes = null, ?int $otpLength = null): array
    {
        $authKey = $this->authKey ?: Setting::get('msg91_auth_key', config('services.msg91.auth_key', env('MSG91_AUTH_KEY', '')));
        $templateId = $this->templateId ?: Setting::get('msg91_template_id', config('services.msg91.template_id', env('MSG91_TEMPLATE_ID', '')));
        $senderId = $this->senderId ?: Setting::get('msg91_dlt_sender_id', env('MSG91_SENDER_ID', ''));
        $expiry = $expiryMinutes ?? (int) Setting::get('msg91_otp_expiry', $this->defaultExpiry ?: 10);
        $length = $otpLength ?? (int) Setting::get('msg91_otp_length', $this->defaultLength ?: 4);

        if (empty($authKey)) {
            return [
                'success' => false,
                'message' => 'MSG91 Auth Key is missing. Please set MSG91 Auth Key in Website Settings.',
            ];
        }

        $phone = $this->formatPhone($mobile);

        $payload = [
            'mobile'      => $phone,
            'otp_expiry'  => $expiry,
            'otp_length'  => $length,
        ];

        // Attach template_id if provided
        if (!empty($templateId)) {
            $payload['template_id'] = $templateId;
        }

        // Attach DLT sender ID if provided
        if (!empty($senderId)) {
            $payload['sender'] = $senderId;
        }

        if ($otp !== null && $otp !== '') {
            $payload['otp'] = $otp;
            $payload['OTP'] = $otp;
        }

        try {
            $response = Http::withoutVerifying()->withHeaders([
                'authkey'      => $authKey,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])->timeout(15)->post($this->baseUrl . '/otp?' . http_build_query($payload));

            $body = $response->json() ?? [];

            Log::info('MSG91 Send OTP response', [
                'mobile'  => $phone,
                'status'  => $response->status(),
                'payload' => $payload,
                'body'    => $body,
            ]);

            $isSuccess = $response->successful() && (($body['type'] ?? '') === 'success' || strtolower($body['message'] ?? '') === 'otp sent successfully');

            return [
                'success' => $isSuccess,
                'message' => $body['message'] ?? ($isSuccess ? 'OTP sent successfully via SMS' : 'Failed to send OTP via MSG91'),
                'data'    => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('MSG91 Send OTP Exception: ' . $e->getMessage(), [
                'mobile' => $phone,
            ]);

            return [
                'success' => false,
                'message' => 'MSG91 service error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Resend OTP via Text SMS or Voice call
     *
     * @param string $mobile Customer phone number
     * @param string $retryType 'text' or 'voice'
     * @return array
     */
    public function resendOtp(string $mobile, string $retryType = 'text'): array
    {
        if (empty($this->authKey)) {
            return ['success' => false, 'message' => 'MSG91 Auth Key is missing.'];
        }

        $phone = $this->formatPhone($mobile);

        try {
            $response = Http::withoutVerifying()->withHeaders([
                'authkey' => $this->authKey,
                'Accept'  => 'application/json',
            ])->timeout(15)->get($this->baseUrl . '/otp/retry', [
                'mobile'    => $phone,
                'retrytype' => $retryType,
            ]);

            $body = $response->json() ?? [];
            $isSuccess = $response->successful() && (($body['type'] ?? '') === 'success');

            return [
                'success' => $isSuccess,
                'message' => $body['message'] ?? ($isSuccess ? 'OTP resent successfully.' : 'Failed to resend OTP.'),
                'data'    => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('MSG91 Resend OTP Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Verify OTP against MSG91 (Used when MSG91 auto-generated the OTP)
     *
     * @param string $mobile Customer phone number
     * @param string $otp Code entered by user
     * @return array
     */
    public function verifyOtp(string $mobile, string $otp): array
    {
        if (empty($this->authKey)) {
            return ['success' => false, 'message' => 'MSG91 Auth Key is missing.'];
        }

        $phone = $this->formatPhone($mobile);

        try {
            $response = Http::withoutVerifying()->withHeaders([
                'authkey' => $this->authKey,
                'Accept'  => 'application/json',
            ])->timeout(15)->get($this->baseUrl . '/otp/verify', [
                'mobile' => $phone,
                'otp'    => $otp,
            ]);

            $body = $response->json() ?? [];
            $isSuccess = $response->successful() && (($body['type'] ?? '') === 'success');

            return [
                'success' => $isSuccess,
                'message' => $body['message'] ?? ($isSuccess ? 'OTP verified successfully.' : 'Invalid or expired OTP.'),
                'data'    => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('MSG91 Verify OTP Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
