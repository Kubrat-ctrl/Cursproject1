<?php

namespace App\Http\Requests;

use App\Enums\ScheduleStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScheduleItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'workout_id' => ['required', 'integer', Rule::exists('workouts', 'id')->where('is_active', true)],
            // Тренер — только пользователь с ролью trainer.
            'trainer_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', UserRole::Trainer->value)],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'room' => ['required', 'string', 'max:50'],
            'capacity' => ['required', 'integer', 'min:1', 'max:200'],
            'status' => ['sometimes', Rule::enum(ScheduleStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Поле «:attribute» обязательно для заполнения.',
            'string' => 'Поле «:attribute» должно быть строкой.',
            'integer' => 'Поле «:attribute» должно быть целым числом.',
            'date' => 'Поле «:attribute» должно быть датой и временем.',
            'workout_id.exists' => 'Выбранный вид занятия не найден или не проводится.',
            'trainer_id.exists' => 'Выбранный пользователь не является тренером.',
            'starts_at.after' => 'Занятие нельзя назначить на прошедшее время.',
            'ends_at.after' => 'Окончание занятия должно быть позже начала.',
            'room.max' => 'Название зала не должно быть длиннее :max символов.',
            'capacity.min' => 'Число мест — не менее :min.',
            'capacity.max' => 'Число мест — не более :max.',
            'status.enum' => 'Статус должен быть scheduled или cancelled.',
        ];
    }

    public function attributes(): array
    {
        return [
            'workout_id' => 'вид занятия',
            'trainer_id' => 'тренер',
            'starts_at' => 'начало',
            'ends_at' => 'окончание',
            'room' => 'зал',
            'capacity' => 'число мест',
            'status' => 'статус',
        ];
    }
}
