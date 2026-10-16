<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends push notifications. Native FCM device tokens go straight to Firebase (HTTP v1);
 * legacy "ExponentPushToken[...]" tokens still go through Expo.
 */
class PushService
{
    private const EXPO_URL = 'https://exp.host/--/api/v2/push/send';

    /**
     * @param  array<string, mixed>  $data
     * @return array{ok: bool, error: ?string}
     */
    public function send(string $token, string $title, string $body, array $data = []): array
    {
        try {
            return str_starts_with($token, 'ExponentPushToken')
                ? $this->sendExpo($token, $title, $body, $data)
                : $this->sendFcm($token, $title, $body, $data);
        } catch (\Throwable $e) {
            Log::error('Push failed', ['error' => $e->getMessage()]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public static function tokenType(string $token): string
    {
        return str_starts_with($token, 'ExponentPushToken') ? 'Expo' : 'FCM';
    }

    private function sendExpo(string $token, string $title, string $body, array $data): array
    {
        $result = Http::timeout(15)->post(self::EXPO_URL, [
            'to' => $token, 'title' => $title, 'body' => $body, 'data' => (object) $data, 'sound' => 'default',
        ])->json('data');
        $result = array_is_list($result ?? []) ? ($result[0] ?? []) : $result;

        if (($result['status'] ?? null) === 'ok') {
            return ['ok' => true, 'error' => null];
        }

        Log::warning('Expo push error', ['result' => $result]);

        return ['ok' => false, 'error' => $result['details']['error'] ?? $result['message'] ?? 'Expo error'];
    }

    private function sendFcm(string $token, string $title, string $body, array $data): array
    {
        $credentials = $this->credentials();
        $response = Http::withToken($this->accessToken($credentials))->timeout(15)
            ->post("https://fcm.googleapis.com/v1/projects/{$credentials['project_id']}/messages:send", [
                'message' => [
                    'token' => $token,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => (object) array_map('strval', $data),
                    'android' => ['priority' => 'HIGH', 'notification' => ['sound' => 'default', 'channel_id' => 'meetings']],
                ],
            ]);

        if ($response->successful()) {
            return ['ok' => true, 'error' => null];
        }

        $error = $response->json('error.details.0.errorCode') ?? $response->json('error.status') ?? 'HTTP '.$response->status();
        Log::warning('FCM push error', ['response' => $response->json()]);

        return ['ok' => false, 'error' => $error];
    }

    /** @return array<string, string> */
    private function credentials(): array
    {
        $path = config('services.fcm.credentials');

        if (! $path || ! is_readable($path)) {
            throw new \RuntimeException('FCM service-account file not found.');
        }

        return json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }

    /** @param array<string, string> $credentials */
    private function accessToken(array $credentials): string
    {
        return Cache::remember('fcm_access_token', 3300, function () use ($credentials): string {
            $b64 = fn (string $s): string => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
            $now = time();
            $unsigned = $b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])).'.'.$b64(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));
            openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);

            $token = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $unsigned.'.'.$b64($signature),
            ])->throw()->json('access_token');

            return $token;
        });
    }
}
