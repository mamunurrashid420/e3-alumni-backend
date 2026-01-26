<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected string $token;
    protected string $url = 'https://api.bdbulksms.net/api.php?json';

    public function __construct()
    {
        $this->token = config('services.bdbulksms.token', env('SMS_TOKEN', ''));
    }

    /**
     * Send an SMS using bdbulksms.net API.
     *
     * @param string $to Recipient phone number(s)
     * @param string $message The message content
     * @return bool Success status
     */
    public function sendSms(string $to, string $message): bool
    {
        if (empty($this->token)) {
            Log::error('SMS token is not configured.');
            return false;
        }

        try {
            $data = [
                'to' => $to,
                'message' => $message,
                'token' => $this->token,
            ];

            // Using cURL as requested in the sample, but via Laravel's Http facade for better testability and cleaner code
            // The sample provided used http_build_query which is a POST with application/x-www-form-urlencoded
            $response = Http::asForm()
                ->withOptions([
                    'verify' => false, // As per sample sample CURLOPT_SSL_VERIFYPEER => 0
                ])
                ->post($this->url, $data);

            if ($response->successful()) {
                $result = $response->body();
                Log::info('SMS sent successfully.', [
                    'to' => $to,
                    'response' => $result,
                ]);
                return true;
            }

            Log::error('SMS sending failed.', [
                'to' => $to,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('SMS service error: ' . $e->getMessage(), [
                'to' => $to,
                'exception' => $e,
            ]);
            return false;
        }
    }
}
