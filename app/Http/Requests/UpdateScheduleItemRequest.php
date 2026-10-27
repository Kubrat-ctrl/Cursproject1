<?php

namespace App\Http\Requests;

use App\Enums\ScheduleStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScheduleItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'workout_id' => ['sometimes', 'required', 'integer', Rule::exists('workouts', 'id')->where('is_active', true)],
            'trainer_id' => ['sometimes', 'required', 'integer', Rule::exists('users', 'id')->where('role', UserRole::Trainer->value)],
            'starts_at' => ['sometimes', 'required', 'date'],
            // Если начало не передано, сравнивать окончание не с чем: дальше работает бизнес-логика.
            'ends_at' => ['sometimes', 'required', 'date', Rule::when($this->has('starts_at'), 'after:starts_at')],
            'room' => ['sometimes', 'required', 'string', 'max:50'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1', 'max:200'],
            'status' => ['sometimes', Rule::enum(ScheduleStatus::class)],
        ];
    }

    public function messages(): array
    {
        return (new StoreScheduleItemRequest)->messages();
    }

    public function attributes(): array
    {
        return (new StoreScheduleItemRequest)->attributes();
    }
}
