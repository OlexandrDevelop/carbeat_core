<?php

namespace App\Http\Services;

use App\Enums\AppBrand;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class FcmService
{
    public function hasActiveTokens(User $user, string|AppBrand|null $app = null): bool
    {
        $brand = $this->normalizeApp($app ?? $user->app ?? null);

        return DeviceToken::query()
            ->where('user_id', $user->id)
            ->where('app', $brand)
            ->where('active', true)
            ->exists();
    }

    public function sendToUser(
        User $user,
        string $title,
        string $body,
        array $data = [],
        string|AppBrand|null $app = null
    ): bool {
        $brand = $this->normalizeApp($app ?? $user->app ?? null);

        $tokens = DeviceToken::query()
            ->where('user_id', $user->id)
            ->where('app', $brand)
            ->where('active', true)
            ->pluck('token')
            ->all();

        if ($tokens === []) {
            return false;
        }

        $sentAny = false;
        foreach ($tokens as $token) {
            if ($this->sendToToken($token, $title, $body, $data, $brand)) {
                $sentAny = true;
            }
        }

        return $sentAny;
    }

    public function sendToToken(
        string $token,
        string $title,
        string $body,
        array $data = [],
        string|AppBrand|null $app = null
    ): bool {
        $brand = $this->normalizeApp($app);
        $credentialsPath = $this->resolveCredentialsPath($brand);

        if ($credentialsPath === null || ! is_file($credentialsPath)) {
            logger()->warning('FCM credentials file is not configured', [
                'app' => $brand,
                'path' => $credentialsPath,
            ]);

            return false;
        }

        $credentials = json_decode((string) file_get_contents($credentialsPath), true);
        if (! is_array($credentials) || empty($credentials['project_id'])) {
            logger()->warning('FCM credentials file is invalid', [
                'app' => $brand,
                'path' => $credentialsPath,
            ]);

            return false;
        }

        $accessToken = $this->fetchAccessToken($credentials);
        if ($accessToken === null) {
            return false;
        }

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => array_map(static fn ($value) => (string) $value, $data),
                'android' => [
                    'priority' => 'high',
                ],
                'apns' => [
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'content-available' => 1,
                        ],
                    ],
                ],
            ],
        ];

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->post(
                sprintf('https://fcm.googleapis.com/v1/projects/%s/messages:send', $credentials['project_id']),
                $payload
            );

        if ($response->successful()) {
            return true;
        }

        $responseBody = $response->json();
        $errorCode = data_get($responseBody, 'error.details.0.errorCode');
        if (in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT'], true)) {
            app(DeviceTokenService::class)->deactivate($token);
        }

        logger()->warning('FCM send failed', [
            'app' => $brand,
            'status' => $response->status(),
            'response' => $responseBody,
        ]);

        return false;
    }

    private function fetchAccessToken(array $credentials): ?string
    {
        $jwt = $this->buildJwt($credentials);
        if ($jwt === null) {
            return null;
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (! $response->successful()) {
            logger()->warning('FCM OAuth token request failed', ['response' => $response->json()]);

            return null;
        }

        return $response->json('access_token');
    }

    private function buildJwt(array $credentials): ?string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $now = now()->timestamp;
        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $credentials['client_email'] ?? null,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $unsigned = $header.'.'.$claims;
        $privateKey = $credentials['private_key'] ?? null;
        if (! is_string($privateKey) || $privateKey === '') {
            return null;
        }

        $signature = '';
        if (! openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            return null;
        }

        return $unsigned.'.'.$this->base64UrlEncode($signature);
    }

    private function resolveCredentialsPath(string $app): ?string
    {
        $projects = config('services.fcm.projects', []);
        $path = $projects[$app] ?? null;

        return is_string($path) && $path !== '' ? $path : null;
    }

    private function normalizeApp(string|AppBrand|null $app): string
    {
        if ($app instanceof AppBrand) {
            return $app->value;
        }

        if (is_string($app) && $app !== '') {
            return AppBrand::fromHeader($app)->value;
        }

        $configured = config('app.client');
        if ($configured instanceof AppBrand) {
            return $configured->value;
        }

        return AppBrand::CARBEAT->value;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
