<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PopularWorkoutsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:from'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_format' => 'Поле «:attribute» должно быть датой в формате ГГГГ-ММ-ДД.',
            'integer' => 'Поле «:attribute» должно быть целым числом.',
            'min' => 'Поле «:attribute» должно быть не меньше :min.',
            'max' => 'Поле «:attribute» должно быть не больше :max.',
            'to.after_or_equal' => 'Дата окончания периода не может быть раньше даты начала.',
        ];
    }

    public function attributes(): array
    {
        return [
            'from' => 'начало периода',
            'to' => 'конец периода',
            'limit' => 'количество видов',
        ];
    }
}
