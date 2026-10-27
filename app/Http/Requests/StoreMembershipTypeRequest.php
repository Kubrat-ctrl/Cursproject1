<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMembershipTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Доступ по ролям ограничивается middleware маршрута.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:membership_types,name'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'visits_limit' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Поле «:attribute» обязательно для заполнения.',
            'string' => 'Поле «:attribute» должно быть строкой.',
            'integer' => 'Поле «:attribute» должно быть целым числом.',
            'numeric' => 'Поле «:attribute» должно быть числом.',
            'boolean' => 'Поле «:attribute» должно быть true или false.',
            'name.max' => 'Название не должно быть длиннее :max символов.',
            'name.unique' => 'Тип абонемента с таким названием уже существует.',
            'price.min' => 'Цена должна быть больше нуля.',
            'duration_days.min' => 'Срок действия — не менее :min дня.',
            'visits_limit.min' => 'Лимит посещений — не менее :min. Для безлимита оставьте поле пустым.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'название',
            'price' => 'цена',
            'duration_days' => 'срок действия',
            'visits_limit' => 'лимит посещений',
            'is_active' => 'признак продажи',
        ];
    }
}
