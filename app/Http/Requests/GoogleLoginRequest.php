<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Вход через Google: ID-токен, который выдал Google Identity Services. */
class GoogleLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'credential' => ['required', 'string', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Поле «:attribute» обязательно для заполнения.',
            'string' => 'Поле «:attribute» должно быть строкой.',
            'max' => 'Поле «:attribute» слишком длинное.',
        ];
    }

    public function attributes(): array
    {
        return ['credential' => 'токен Google'];
    }
}
