<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/** Сброс пароля по ссылке из письма (встроенный брокер паролей Laravel). */
class PasswordResetService
{
    /**
     * Отправляет письмо со ссылкой. Результат не зависит от того, есть ли такой email:
     * по ответу нельзя проверять, зарегистрирован ли адрес.
     */
    public function sendLink(string $email): void
    {
        try {
            $status = Password::sendResetLink(['email' => $email]);
        } catch (\Throwable $e) {
            Log::error('Не удалось отправить письмо для сброса пароля', ['error' => $e->getMessage()]);

            abort(503, 'Не удалось отправить письмо. Проверьте настройки почты и повторите попытку позже');
        }

        Log::info('Запрос сброса пароля', ['status' => $status]);
    }

    public function reset(array $data): void
    {
        $status = Password::reset(
            Arr::only($data, ['email', 'password', 'password_confirmation', 'token']),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password, // хешируется приведением hashed
                    'remember_token' => Str::random(60),
                ])->save();

                // Старые токены больше не действуют
                $user->tokens()->delete();

                event(new PasswordReset($user));

                Log::info('Пароль изменён по ссылке из письма', ['user_id' => $user->id]);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw BusinessException::unprocessable(
                'Ссылка для сброса пароля недействительна или устарела. Запросите новую');
        }
    }
}
