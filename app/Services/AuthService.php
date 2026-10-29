<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'phone' => $data['phone'] ?? null,
            'role' => UserRole::Client,
        ]);

        return [$user, $user->createToken('api')->plainTextToken];
    }

    public function login(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            abort(401, 'Неверный email или пароль');
        }
        if ($user->blocked_at !== null) {
            abort(403, 'Учётная запись заблокирована');
        }

        return [$user, $user->createToken('api')->plainTextToken];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}