<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScheduleItemIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trainer_id' => ['sometimes', 'integer', 'exists:users,id'],
            'workout_id' => ['sometimes', 'integer', 'exists:workouts,id'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
            'search' => ['sometimes', 'string', 'max:100'],
            // Поля сортировки проверяются по белому списку: в orderBy() попадает только известный столбец.
            'sort' => ['sometimes', Rule::in(['starts_at', 'capacity', 'room'])],
            'order' => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'integer' => 'Поле «:attribute» должно быть целым числом.',
            'string' => 'Поле «:attribute» должно быть строкой.',
            'date' => 'Поле «:attribute» должно быть датой.',
            'min' => 'Поле «:attribute» должно быть не меньше :min.',
            'max' => 'Поле «:attribute» должно быть не больше :max.',
            'exists' => 'Выбранное значение поля «:attribute» не найдено.',
            'date_to.after_or_equal' => 'Конец периода не может быть раньше начала.',
            'sort.in' => 'Сортировать можно по полям: starts_at, capacity, room.',
            'order.in' => 'Порядок сортировки: asc или desc.',
        ];
    }

    public function attributes(): array
    {
        return [
            'trainer_id' => 'тренер',
            'workout_id' => 'вид занятия',
            'date_from' => 'начало периода',
            'date_to' => 'конец периода',
            'search' => 'поиск',
            'sort' => 'сортировка',
            'order' => 'порядок',
            'per_page' => 'размер страницы',
            'page' => 'страница',
        ];
    }
}
