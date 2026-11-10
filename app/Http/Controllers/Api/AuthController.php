<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    /**
     * Регистрация клиента
     *
     * Доступ: любой гость.
     *
     * @group Аутентификация
     * @bodyParam name string required Имя. Example: Новый Клиент
     * @bodyParam email string required Уникальный email. Example: new@fitclub.test
     * @bodyParam password string required Не короче 8 символов. Example: secret123
     * @bodyParam password_confirmation string required Повтор пароля. Example: secret123
     * @bodyParam phone string Телефон. Example: +77010000010
     * @response 201 {"user": {"id": 11, "name": "Новый Клиент", "email": "new@fitclub.test", "role": "client"}, "token": "1|AbCdEf..."}
     * @response 422 {"message": "Ошибка валидации", "errors": {"field": ["Сообщение об ошибке"]}}
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        [$user, $token] = $this->auth->register($request->validated());

        return response()->json(['user' => new UserResource($user), 'token' => $token], 201);
    }

    /**
     * Вход: возвращает токен доступа Sanctum
     *
     * Доступ: любой гость.
     *
     * @group Аутентификация
     * @bodyParam email string required Email. Example: admin@fitclub.test
     * @bodyParam password string required Пароль. Example: password
     * @response 200 {"user": {"id": 1, "name": "Анна Воронова", "email": "admin@fitclub.test", "role": "admin"}, "token": "2|XyZ..."}
     * @response 401 {"message": "Неверный email или пароль"}
     * @response 403 {"message": "Учётная запись заблокирована"}
     * @response 422 {"message": "Ошибка валидации", "errors": {"field": ["Сообщение об ошибке"]}}
     */
    public function login(LoginRequest $request): JsonResponse
    {
        [$user, $token] = $this->auth->login($request->validated());

        return response()->json(['user' => new UserResource($user), 'token' => $token]);
    }

    public function logout(Request $request): Response
    {
        $this->auth->logout($request->user());

        return response()->noContent();
    }
}   //

