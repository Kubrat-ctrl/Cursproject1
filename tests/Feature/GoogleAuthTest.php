<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    private const CLIENT_ID = 'test-client-id.apps.googleusercontent.com';

    private const GOOGLE_SUB = '110000000000000000001';

    /** Подменяет ответ Google (tokeninfo): реальные запросы к Google в тестах не выполняются. */
    private function fakeGoogle(array $override = [], int $status = 200): void
    {
        config(['services.google.client_id' => self::CLIENT_ID]);

        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(array_merge([
                'iss' => 'https://accounts.google.com',
                'aud' => self::CLIENT_ID,
                'sub' => self::GOOGLE_SUB,
                'email' => 'new.user@gmail.com',
                'email_verified' => 'true',
                'name' => 'Новый Пользователь',
                'exp' => (string) (time() + 3600),
            ], $override), $status),
        ]);
    }

    private function loginWithGoogle()
    {
        return $this->postJson('/api/v1/auth/google', ['credential' => 'fake-id-token']);
    }

    public function test_first_google_login_creates_client_and_returns_token(): void
    {
        $this->fakeGoogle();

        $this->loginWithGoogle()
            ->assertOk()
            ->assertJsonPath('user.email', 'new.user@gmail.com')
            ->assertJsonPath('user.name', 'Новый Пользователь')
            ->assertJsonPath('user.role', 'client')
            ->assertJsonStructure(['token'])
            ->assertJsonMissingPath('user.password')
            ->assertJsonMissingPath('user.google_id');

        $this->assertDatabaseHas('users', [
            'email' => 'new.user@gmail.com',
            'google_id' => self::GOOGLE_SUB,
            'role' => 'client',
        ]);
    }

    public function test_repeated_google_login_does_not_create_duplicates(): void
    {
        $this->fakeGoogle();

        $this->loginWithGoogle()->assertOk();
        $this->loginWithGoogle()->assertOk();

        $this->assertDatabaseCount('users', 1);
    }

    public function test_google_is_linked_to_existing_account_with_same_email(): void
    {
        $user = User::factory()->client()->create(['email' => 'petrov.real@gmail.com']);
        $this->fakeGoogle(['email' => 'petrov.real@gmail.com']);

        $this->loginWithGoogle()
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);

        $this->assertSame(self::GOOGLE_SUB, $user->fresh()->google_id);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_google_token_for_another_application_is_rejected(): void
    {
        $this->fakeGoogle(['aud' => 'other-application.apps.googleusercontent.com']);

        $this->loginWithGoogle()
            ->assertStatus(401)
            ->assertJsonPath('message', 'Не удалось подтвердить вход через Google');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_unverified_google_email_is_rejected(): void
    {
        $this->fakeGoogle(['email_verified' => 'false']);

        $this->loginWithGoogle()->assertStatus(401);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_token_rejected_by_google_gives_401(): void
    {
        $this->fakeGoogle(['error' => 'invalid_token'], 400);

        $this->loginWithGoogle()
            ->assertStatus(401)
            ->assertJsonPath('message', 'Не удалось подтвердить вход через Google');
    }

    public function test_blocked_user_cannot_login_with_google(): void
    {
        User::factory()->client()->create([
            'email' => 'blocked@gmail.com',
            'blocked_at' => now(),
        ]);
        $this->fakeGoogle(['email' => 'blocked@gmail.com']);

        $this->loginWithGoogle()
            ->assertStatus(403)
            ->assertJsonPath('message', 'Учётная запись заблокирована');
    }

    public function test_account_linked_to_another_google_is_rejected(): void
    {
        User::factory()->client()->create([
            'email' => 'linked@gmail.com',
            'google_id' => '999999999999999999999',
        ]);
        $this->fakeGoogle(['email' => 'linked@gmail.com']);

        $this->loginWithGoogle()->assertStatus(409);
    }

    public function test_google_login_requires_client_id_in_config(): void
    {
        config(['services.google.client_id' => null]);

        $this->loginWithGoogle()
            ->assertStatus(503)
            ->assertJsonPath('message', 'Вход через Google не настроен на сервере');
    }

    public function test_google_login_requires_credential(): void
    {
        $this->postJson('/api/v1/auth/google', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['credential']);
    }
}
