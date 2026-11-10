<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Профиль текущего пользователя
     *
     * Доступ: любой авторизованный пользователь.
     *
     * @group Профиль
     * @authenticated
     * @response 200 {"data": {"id": 5, "name": "Олег Петров", "email": "petrov@fitclub.test", "role": "client", "phone": "+77010000004", "avatar_path": null, "blocked": false, "created_at": "2026-10-22T10:00:00+05:00"}}
     * @response 401 {"message": "Требуется авторизация"}
     */
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function update(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();
        $user->update($request->validated());

        return new UserResource($user);
    }
}