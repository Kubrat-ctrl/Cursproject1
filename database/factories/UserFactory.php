<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /** Хеш пароля считается один раз на все созданные записи. */
    protected static ?string $password = null;

    public function definition(): array
    {
        $faker = fake('ru_RU');

        return [
            'name' => $faker->name(),
            'email' => $faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Client,
            'phone' => $faker->numerify('+79#########'),
            'avatar_path' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function client(): static
    {
        return $this->state(fn () => ['role' => UserRole::Client]);
    }

    public function trainer(): static
    {
        return $this->state(fn () => ['role' => UserRole::Trainer]);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => UserRole::Admin]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
