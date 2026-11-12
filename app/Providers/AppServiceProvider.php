<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Письмо для сброса пароля: ссылка ведёт на страницу клиентского приложения,
        // текст письма — на русском языке.
        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $url = rtrim((string) config('app.frontend_url'), '/')
                .'/reset-password?token='.$token
                .'&email='.urlencode($notifiable->getEmailForPasswordReset());

            $data = [
                'name' => $notifiable->name,
                'url' => $url,
                'minutes' => (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
                'appName' => config('app.name'),
            ];

            return (new MailMessage)
                ->subject('Сброс пароля — '.config('app.name'))
                ->view('emails.reset-password', $data)
                ->text('emails.reset-password-text', $data);
        });
    }
}
