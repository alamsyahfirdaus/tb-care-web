<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Google\Auth\Credentials\ServiceAccountCredentials;

class FcmService
{
    /**
     * Send push notification to a device token or multiple tokens using FCM HTTP v1 API.
     *
     * @param string|array $tokens
     * @param string $title
     * @param string $body
     * @param array $data
     * @return bool
     */
    public static function sendNotification($tokens, $title, $body, $data = [])
    {
        $projectId = config('services.fcm.project_id');
        $credentialsPath = config('services.fcm.credentials');

        if (empty($projectId)) {
            Log::warning('FCM Project ID is not configured.');
            return false;
        }

        if (empty($credentialsPath)) {
            Log::warning('FCM credentials path is not configured.');
            return false;
        }

        // Tentukan path absolut untuk file kredensial
        $absolutePath = $credentialsPath;
        if (!file_exists($absolutePath)) {
            $absolutePath = base_path($credentialsPath);
        }

        if (!file_exists($absolutePath)) {
            Log::warning('FCM Service Account credentials file does not exist at path: ' . $credentialsPath);
            return false;
        }

        // Peroleh OAuth 2.0 Access Token dengan Cache (berlaku ~58 menit)
        try {
            $accessToken = Cache::remember('fcm_access_token', 3500, function () use ($absolutePath) {
                $credentials = new ServiceAccountCredentials(
                    'https://www.googleapis.com/auth/firebase.messaging',
                    $absolutePath
                );
                $tokenArray = $credentials->fetchAuthToken();
                return $tokenArray['access_token'] ?? null;
            });

            if (empty($accessToken)) {
                Cache::forget('fcm_access_token');
                Log::error('Failed to retrieve FCM OAuth 2.0 access token.');
                return false;
            }
        } catch (\Throwable $e) {
            Log::error('Exception retrieving FCM OAuth 2.0 access token.', ['exception' => $e->getMessage()]);
            return false;
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ];

        // Format data payload (harus string)
        $dataPayload = [];
        foreach (array_merge([
            'title' => $title,
            'body' => $body,
        ], $data) as $key => $val) {
            $dataPayload[$key] = (string) $val;
        }

        $message = [
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $dataPayload,
        ];

        $tokenList = is_array($tokens) ? array_values(array_filter($tokens)) : [$tokens];
        $success = true;

        foreach ($tokenList as $token) {
            if (empty($token)) continue;

            $payload = [
                'message' => array_merge([
                    'token' => $token,
                ], $message)
            ];

            try {
                $response = Http::withHeaders($headers)->post($url, $payload);

                if ($response->successful()) {
                    Log::info('FCM notification sent successfully.');
                } else {
                    $success = false;
                    $status = $response->status();

                    Log::error('FCM notification failed.', [
                        'status' => $status,
                        'material_id' => $data['material_id'] ?? null,
                    ]);

                    // Tangani token invalid/unregistered
                    if ($status == 404 || $status == 410) {
                        $errBody = $response->json();
                        $errorCode = $errBody['error']['details'][0]['errorCode'] ?? $errBody['error']['status'] ?? '';
                        if ($errorCode === 'UNREGISTERED' || $status == 404) {
                            // Nonaktifkan token invalid pada database
                            \App\Models\User::where('fcm_token', $token)->update(['fcm_token' => null]);
                            Log::info('Expired/Unregistered FCM token removed from database.');
                        }
                    }
                }
            } catch (\Throwable $e) {
                $success = false;
                Log::error('FCM notification exception.', [
                    'exception' => $e->getMessage(),
                    'material_id' => $data['material_id'] ?? null,
                ]);
            }
        }

        return $success;
    }
}
