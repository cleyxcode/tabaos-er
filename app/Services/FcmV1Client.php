<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class FcmV1Client
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const MESSAGING_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    /**
     * @param  array<string, string>  $data
     */
    public function sendToDevice(string $token, string $title, string $body, array $data = []): bool
    {
        if (blank($token)) {
            return false;
        }

        $credentials = $this->loadCredentials();
        if ($credentials === null) {
            Log::warning('FCM V1: FIREBASE_CREDENTIALS belum dikonfigurasi, push dilewati.');

            return false;
        }

        $projectId = (string) config('services.fcm.project_id', $credentials['project_id'] ?? '');
        if ($projectId === '') {
            Log::error('FCM V1: FIREBASE_PROJECT_ID kosong.');

            return false;
        }

        try {
            $accessToken = $this->getAccessToken($credentials);
            $stringData = collect($data)
                ->map(fn ($value) => is_scalar($value) ? (string) $value : json_encode($value))
                ->merge([
                    'title' => $title,
                    'body' => $body,
                ])
                ->all();

            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => $stringData,
                        'android' => [
                            'priority' => 'HIGH',
                            'notification' => [
                                'channel_id' => 'tabaos_admin',
                                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                                'sound' => 'default',
                            ],
                        ],
                        'apns' => [
                            'headers' => [
                                'apns-priority' => '10',
                            ],
                            'payload' => [
                                'aps' => [
                                    'content-available' => 1,
                                    'sound' => 'default',
                                ],
                            ],
                        ],
                    ],
                ]);

            if ($response->failed()) {
                Log::error('FCM V1 gagal kirim', [
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('FCM V1 exception: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadCredentials(): ?array
    {
        $path = config('services.fcm.credentials');
        if (! is_string($path) || $path === '' || ! is_readable($path)) {
            return null;
        }

        $json = file_get_contents($path);
        if ($json === false) {
            return null;
        }

        $credentials = json_decode($json, true);
        if (! is_array($credentials)) {
            return null;
        }

        foreach (['client_email', 'private_key', 'project_id'] as $required) {
            if (empty($credentials[$required])) {
                Log::error("FCM V1: field {$required} tidak ada di service account JSON.");

                return null;
            }
        }

        return $credentials;
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function getAccessToken(array $credentials): string
    {
        $cacheKey = 'fcm_v1_access_token_' . sha1((string) $credentials['client_email']);

        $token = Cache::get($cacheKey);
        if (is_string($token) && $token !== '') {
            return $token;
        }

        $jwt = $this->createJwtAssertion(
            (string) $credentials['client_email'],
            (string) $credentials['private_key'],
        );

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Gagal mengambil FCM access token: ' . $response->body());
        }

        $accessToken = (string) ($response->json('access_token') ?? '');
        if ($accessToken === '') {
            throw new RuntimeException('FCM access token kosong dari Google OAuth.');
        }

        $expiresIn = (int) ($response->json('expires_in') ?? 3600);
        Cache::put($cacheKey, $accessToken, max(60, $expiresIn - 120));

        return $accessToken;
    }

    private function createJwtAssertion(string $clientEmail, string $privateKey): string
    {
        $now = time();
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ], JSON_THROW_ON_ERROR));

        $payload = $this->base64UrlEncode(json_encode([
            'iss' => $clientEmail,
            'sub' => $clientEmail,
            'aud' => self::TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
            'scope' => self::MESSAGING_SCOPE,
        ], JSON_THROW_ON_ERROR));

        $input = $header . '.' . $payload;

        $signature = '';
        $signed = openssl_sign($input, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (! $signed) {
            throw new RuntimeException('Gagal menandatangani JWT FCM.');
        }

        return $input . '.' . $this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
