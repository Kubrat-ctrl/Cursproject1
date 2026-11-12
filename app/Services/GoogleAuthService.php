<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Exceptions\BusinessException;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/** Вход и регистрация через Google: токен проверяется, пользователь находится или создаётся. */
class GoogleAuthService
{
    public function __construct(private readonly GoogleTokenVerifier $verifier)
    {
    }

    /** @return array{0: User, 1: string} пользователь и токен Sanctum */
    public function login(string $credential): array
    {
        $google = $this->verifier->verify($credential);

        $user = User::where('google_id', $google['sub'])->first()
            ?? User::where('email', $google['email'])->first();

        if ($user === null) {
            // Первый вход: создаём клиента. Пароль случайный: входить по паролю
            // можно после сброса пароля по ссылке из письма.
            $user = User::create([
                'name' => $google['name'],
                'email' => $google['email'],
                'password' => Str::random(40),
                'role' => UserRole::Client,
            ]);
            $user->forceFill(['google_id' => $google['sub'], 'email_verified_at' => now()])->save();

            Log::info('Регистрация через Google', ['user_id' => $user->id]);
        } elseif ($user->google_id === null) {
            // Учётная запись уже есть: привязываем Google (email подтверждён Google).
            $user->forceFill([
                'google_id' => $google['sub'],
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            Log::info('Google привязан к учётной записи', ['user_id' => $user->id]);
        } elseif ($user->google_id !== $google['sub']) {
            throw BusinessException::conflict('Эта учётная запись уже привязана к другому аккаунту Google');
        }

        if ($user->blocked_at !== null) {
            abort(403, 'Учётная запись заблокирована');
        }

        Log::info('Вход через Google', ['user_id' => $user->id]);

        return [$user, $user->createToken('api')->plainTextToken];
    }
}
