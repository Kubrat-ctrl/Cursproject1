<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Установка нового пароля по ссылке из письма. */
class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Поле «:attribute» обязательно для заполнения.',
            'string' => 'Поле «:attribute» должно быть строкой.',
            'email.email' => 'Введите корректный адрес электронной почты.',
            'password.min' => 'Пароль должен содержать не менее :min символов.',
            'password.confirmed' => 'Пароль и подтверждение не совпадают.',
        ];
    }

    public function attributes(): array
    {
        return [
            'token' => 'токен',
            'email' => 'email',
            'password' => 'пароль',
        ];
    }
}
