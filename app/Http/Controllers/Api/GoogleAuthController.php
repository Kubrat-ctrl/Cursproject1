<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GoogleLoginRequest;
use App\Http\Resources\UserResource;
use App\Services\GoogleAuthService;
use Illuminate\Http\JsonResponse;

class GoogleAuthController extends Controller
{
    public function __construct(private readonly GoogleAuthService $google)
    {
    }

    /**
     * Вход или регистрация через Google
     *
     * Доступ: любой гость. Клиент получает ID-токен от Google Identity Services (поле credential)
     * и отправляет его сюда. Сервер проверяет токен у Google; если пользователя с таким email нет,
     * создаётся клиент, если есть, к его учётной записи привязывается аккаунт Google.
     *
     * @group Аутентификация
     * @bodyParam credential string required ID-токен Google. Example: eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMTAifQ.signature
     * @response 200 {"user": {"id": 12, "name": "Иван Иванов", "email": "ivan@gmail.com", "role": "client"}, "token": "3|AbCdEf..."}
     * @response 401 {"message": "Не удалось подтвердить вход через Google"}
     * @response 403 {"message": "Учётная запись заблокирована"}
     * @response 409 {"message": "Эта учётная запись уже привязана к другому аккаунту Google"}
     * @response 422 {"message": "Ошибка валидации", "errors": {"credential": ["Поле «токен Google» обязательно для заполнения."]}}
     * @response 503 {"message": "Вход через Google не настроен на сервере"}
     */
    public function store(GoogleLoginRequest $request): JsonResponse
    {
        [$user, $token] = $this->google->login($request->validated('credential'));

        return response()->json(['user' => new UserResource($user), 'token' => $token]);
    }
}
