<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_register_and_gets_client_role_and_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Новый Клиент',
            'email' => 'new@fitclub.test',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('user.email', 'new@fitclub.test')
            ->assertJsonPath('user.role', 'client')
            ->assertJsonStructure(['token'])
            ->assertJsonMissingPath('user.password');

        $this->assertDatabaseHas('users', ['email' => 'new@fitclub.test', 'role' => 'client']);
    }

    public function test_registration_validates_input(): void
    {
        User::factory()->create(['email' => 'taken@fitclub.test']);

        $this->postJson('/api/v1/auth/register', [
            'name' => '',
            'email' => 'taken@fitclub.test',
            'password' => 'short',
            'password_confirmation' => 'other',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Ошибка валидации')
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        User::factory()->create(['email' => 'user@fitclub.test', 'password' => 'password']);

        $this->postJson('/api/v1/auth/login', ['email' => 'user@fitclub.test', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('user.email', 'user@fitclub.test')
            ->assertJsonStructure(['token']);
    }

    public function test_login_with_wrong_password_is_rejected(): void
    {
        User::factory()->create(['email' => 'user@fitclub.test', 'password' => 'password']);

        $this->postJson('/api/v1/auth/login', ['email' => 'user@fitclub.test', 'password' => 'wrong'])
            ->assertStatus(401)
            ->assertJsonPath('message', 'Неверный email или пароль');
    }

    public function test_blocked_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'blocked@fitclub.test',
            'password' => 'password',
            'blocked_at' => now(),
        ]);

        $this->postJson('/api/v1/auth/login', ['email' => 'blocked@fitclub.test', 'password' => 'password'])
            ->assertStatus(403);
    }

    public function test_protected_route_requires_token(): void
    {
        $this->getJson('/api/v1/profile')
            ->assertStatus(401)
            ->assertJsonPath('message', 'Требуется авторизация');
    }

    public function test_authenticated_user_sees_own_profile(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role', 'client');
    }

    public function test_logout_deletes_the_token(): void
    {
        $user = User::factory()->create(['email' => 'user@fitclub.test', 'password' => 'password']);
        $token = $this->postJson('/api/v1/auth/login', ['email' => 'user@fitclub.test', 'password' => 'password'])
            ->json('token');
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
