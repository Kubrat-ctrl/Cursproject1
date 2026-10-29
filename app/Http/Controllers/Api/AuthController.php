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

    public function register(RegisterRequest $request): JsonResponse
    {
        [$user, $token] = $this->auth->register($request->validated());

        return response()->json(['user' => new UserResource($user), 'token' => $token], 201);
    }

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

