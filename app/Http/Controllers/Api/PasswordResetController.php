<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;

class PasswordResetController extends Controller
{
    public function __construct(private readonly PasswordResetService $passwords)
    {
    }

    /**
     * Забыли пароль: отправка письма со ссылкой
     *
     * Доступ: любой гость. Письмо уходит на указанный адрес, если он зарегистрирован; ответ
     * всегда одинаковый, чтобы нельзя было проверять наличие адреса. Ссылка действует 60 минут.
     *
     * @group Аутентификация
     * @bodyParam email string required Email учётной записи. Example: petrov@fitclub.test
     * @response 200 {"message": "Если такой email зарегистрирован, на него отправлено письмо со ссылкой для сброса пароля."}
     * @response 422 {"message": "Ошибка валидации", "errors": {"email": ["Введите корректный адрес электронной почты."]}}
     * @response 503 {"message": "Не удалось отправить письмо. Проверьте настройки почты и повторите попытку позже"}
     */
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $this->passwords->sendLink($request->validated('email'));

        return response()->json([
            'message' => 'Если такой email зарегистрирован, на него отправлено письмо со ссылкой для сброса пароля.',
        ]);
    }

    /**
     * Новый пароль по ссылке из письма
     *
     * Доступ: любой гость. Токен и email берутся из ссылки. После смены пароля все выданные
     * токены доступа пользователя удаляются, войти нужно заново.
     *
     * @group Аутентификация
     * @bodyParam token string required Токен из ссылки в письме. Example: 5f2c1d9e0a7b4c3d8e6f
     * @bodyParam email string required Email из ссылки в письме. Example: petrov@fitclub.test
     * @bodyParam password string required Новый пароль, не короче 8 символов. Example: new-secret-123
     * @bodyParam password_confirmation string required Повтор пароля. Example: new-secret-123
     * @response 200 {"message": "Пароль изменён. Войдите с новым паролем."}
     * @response 422 {"message": "Ссылка для сброса пароля недействительна или устарела. Запросите новую"}
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $this->passwords->reset($request->validated() + $request->only('password_confirmation'));

        return response()->json(['message' => 'Пароль изменён. Войдите с новым паролем.']);
    }
}
