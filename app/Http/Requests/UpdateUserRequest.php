<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['sometimes', 'required', Rule::enum(UserRole::class)],
            'blocked' => ['sometimes', 'required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Поле «:attribute» не может быть пустым.',
            'enum' => 'Поле «:attribute» должно быть одним из: client, trainer, admin.',
            'boolean' => 'Поле «:attribute» должно быть true или false.',
        ];
    }

    public function attributes(): array
    {
        return [
            'role' => 'роль',
            'blocked' => 'блокировка',
        ];
    }
}
