<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Запрос письма для сброса пароля. */
class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Укажите email.',
            'email.email' => 'Введите корректный адрес электронной почты.',
            'email.max' => 'Email не должен быть длиннее :max символов.',
        ];
    }
}
