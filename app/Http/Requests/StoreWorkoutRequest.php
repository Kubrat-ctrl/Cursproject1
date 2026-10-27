<?php

namespace App\Http\Requests;

use App\Enums\WorkoutLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:workouts,name'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:240'],
            'level' => ['required', Rule::enum(WorkoutLevel::class)],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Поле «:attribute» обязательно для заполнения.',
            'string' => 'Поле «:attribute» должно быть строкой.',
            'integer' => 'Поле «:attribute» должно быть целым числом.',
            'boolean' => 'Поле «:attribute» должно быть true или false.',
            'name.max' => 'Название не должно быть длиннее :max символов.',
            'name.unique' => 'Вид занятия с таким названием уже существует.',
            'description.max' => 'Описание не должно быть длиннее :max символов.',
            'duration_minutes.min' => 'Длительность — не менее :min минут.',
            'duration_minutes.max' => 'Длительность — не более :max минут.',
            'level.enum' => 'Уровень должен быть одним из: beginner, intermediate, advanced.',
            'image.image' => 'Файл должен быть изображением.',
            'image.max' => 'Размер изображения — не более 2 МБ.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'название',
            'description' => 'описание',
            'duration_minutes' => 'длительность',
            'level' => 'уровень',
            'image' => 'изображение',
            'is_active' => 'признак проведения',
        ];
    }
}
