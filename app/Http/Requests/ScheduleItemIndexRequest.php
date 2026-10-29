<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleItemIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trainer_id' => ['nullable', 'integer', 'min:1'],
            'workout_id' => ['nullable', 'integer', 'min:1'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'integer' => 'Поле «:attribute» должно быть целым числом.',
            'min' => 'Поле «:attribute» должно быть не меньше :min.',
            'date_format' => 'Поле «:attribute» должно быть датой в формате ГГГГ-ММ-ДД.',
        ];
    }

    public function attributes(): array
    {
        return [
            'trainer_id' => 'тренер',
            'workout_id' => 'вид занятия',
            'date' => 'дата',
            'page' => 'страница',
        ];
    }
}
