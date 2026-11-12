<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Проверка ID-токена, который браузер получает от Google Identity Services.
 * Токен проверяет сам Google (tokeninfo); дополнительно сверяются получатель токена
 * (aud — наш Client ID), издатель и подтверждённость email.
 */
class GoogleTokenVerifier
{
    private const TOKENINFO_URL = 'https://oauth2.googleapis.com/tokeninfo';

    private const DENIED = 'Не удалось подтвердить вход через Google';

    /** @return array{sub: string, email: string, name: string} */
    public function verify(string $idToken): array
    {
        $clientId = config('services.google.client_id');

        if (! $clientId) {
            abort(503, 'Вход через Google не настроен на сервере');
        }

        try {
            $response = Http::timeout(10)->get(self::TOKENINFO_URL, ['id_token' => $idToken]);
        } catch (ConnectionException) {
            abort(503, 'Не удалось связаться с Google. Попробуйте позже');
        }

        if (! $response->ok()) {
            Log::warning('Google отклонил токен входа', ['status' => $response->status()]);
            abort(401, self::DENIED);
        }

        $payload = $response->json();

        $issuerOk = in_array($payload['iss'] ?? null,
            ['accounts.google.com', 'https://accounts.google.com'], true);
        $emailVerified = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (($payload['aud'] ?? null) !== $clientId
            || ! $issuerOk
            || ! $emailVerified
            || empty($payload['sub'])
            || empty($payload['email'])
            || (int) ($payload['exp'] ?? 0) < time()) {
            Log::warning('Токен Google не прошёл проверку полей');
            abort(401, self::DENIED);
        }

        $email = strtolower((string) $payload['email']);

        return [
            'sub' => (string) $payload['sub'],
            'email' => $email,
            'name' => trim((string) ($payload['name'] ?? '')) ?: Str::before($email, '@'),
        ];
    }
}
