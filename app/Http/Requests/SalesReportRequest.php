<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalesReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Поле «:attribute» обязательно для заполнения.',
            'date_format' => 'Поле «:attribute» должно быть датой в формате ГГГГ-ММ-ДД.',
            'to.after_or_equal' => 'Дата окончания периода не может быть раньше даты начала.',
        ];
    }

    public function attributes(): array
    {
        return [
            'from' => 'начало периода',
            'to' => 'конец периода',
        ];
    }
}
