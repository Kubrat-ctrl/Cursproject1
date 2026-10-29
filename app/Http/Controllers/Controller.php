<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * ПР7: аутентификации ещё нет, поэтому пользователя передаём параметром user_id.
     * В ПР8 этот метод будет заменён на $request->user().
     */
    protected function actingUser(Request $request): User
    {
        return $request->user() ?? User::findOrFail($request->integer('user_id'));
    }
}
