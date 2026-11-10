<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UserIndexRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(private readonly UserService $users)
    {
    }

    /**
     * Список пользователей
     *
     * Доступ: администратор.
     *
     * @group Пользователи
     * @authenticated
     * @queryParam role string Фильтр по роли: client, trainer, admin. Example: trainer
     * @queryParam page integer Номер страницы. Example: 1
     * @response 200 {"data": [{"id": 5, "name": "Олег Петров", "email": "petrov@fitclub.test", "role": "client", "phone": "+77010000004", "avatar_path": null, "blocked": false, "created_at": "2026-10-22T10:00:00+05:00"}], "links": {"first": "...", "last": "...", "prev": null, "next": "..."}, "meta": {"current_page": 1, "last_page": 1, "per_page": 15, "total": 1}}
     * @response 401 {"message": "Требуется авторизация"}
     * @response 403 {"message": "Недостаточно прав для этого действия"}
     * @response 422 {"message": "Ошибка валидации", "errors": {"field": ["Сообщение об ошибке"]}}
     */
    public function index(UserIndexRequest $request): AnonymousResourceCollection
    {
        return UserResource::collection($this->users->paginate($request->validated()));
    }

    /**
     * Смена роли и блокировка пользователя
     *
     * Доступ: администратор.
     *
     * @group Пользователи
     * @authenticated
     * @urlParam user integer required Идентификатор пользователя. Example: 5
     * @bodyParam role string client, trainer или admin. Example: trainer
     * @bodyParam blocked boolean Заблокировать или разблокировать. Example: false
     * @response 200 {"data": {"id": 5, "name": "Олег Петров", "email": "petrov@fitclub.test", "role": "client", "phone": "+77010000004", "avatar_path": null, "blocked": false, "created_at": "2026-10-22T10:00:00+05:00"}}
     * @response 401 {"message": "Требуется авторизация"}
     * @response 403 {"message": "Недостаточно прав для этого действия"}
     * @response 404 {"message": "Пользователь не найден"}
     * @response 409 {"message": "Нельзя снять роль тренера: у него есть предстоящие занятия"}
     * @response 422 {"message": "Ошибка валидации", "errors": {"field": ["Сообщение об ошибке"]}}
     */
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        return new UserResource($this->users->update($user, $request->validated()));
    }
}
