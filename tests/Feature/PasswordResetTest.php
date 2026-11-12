<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const SENT = 'Если такой email зарегистрирован, на него отправлено письмо со ссылкой для сброса пароля.';

    public function test_reset_link_is_sent_to_registered_email(): void
    {
        Notification::fake();
        $user = User::factory()->client()->create();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath('message', self::SENT);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_link_leads_to_frontend_page(): void
    {
        Notification::fake();
        $user = User::factory()->client()->create();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $url = $notification->toMail($user)->viewData['url'];

            return str_starts_with($url, rtrim(config('app.frontend_url'), '/').'/reset-password?token=')
                && str_contains($url, 'email='.urlencode($user->email));
        });
    }

    public function test_unknown_email_gets_the_same_answer_and_no_mail(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com'])
            ->assertOk()
            ->assertJsonPath('message', self::SENT);

        Notification::assertNothingSent();
    }

    public function test_forgot_password_validates_email(): void
    {
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_password_is_changed_by_valid_token_and_old_tokens_are_revoked(): void
    {
        $user = User::factory()->client()->create();
        $user->createToken('api');
        $token = Password::createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-pass-123',
            'password_confirmation' => 'new-pass-123',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Пароль изменён. Войдите с новым паролем.');

        $this->assertTrue(Hash::check('new-pass-123', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'new-pass-123',
        ])->assertOk();
    }

    public function test_reset_token_cannot_be_used_twice(): void
    {
        $user = User::factory()->client()->create();
        $token = Password::createToken($user);
        $payload = [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-pass-123',
            'password_confirmation' => 'new-pass-123',
        ];

        $this->postJson('/api/v1/auth/reset-password', $payload)->assertOk();
        $this->postJson('/api/v1/auth/reset-password', $payload)->assertStatus(422);
    }

    public function test_invalid_reset_token_is_rejected(): void
    {
        $user = User::factory()->client()->create();

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'wrong-token',
            'email' => $user->email,
            'password' => 'new-pass-123',
            'password_confirmation' => 'new-pass-123',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Ссылка для сброса пароля недействительна или устарела. Запросите новую');
    }

    public function test_reset_password_validates_input(): void
    {
        $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'x',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'other',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }
}
