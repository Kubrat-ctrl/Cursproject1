<?php

namespace App\Http\Requests;

use App\Enums\WorkoutLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes', 'required', 'string', 'max:100',
                Rule::unique('workouts', 'name')->ignore($this->route('workout')),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'duration_minutes' => ['sometimes', 'required', 'integer', 'min:15', 'max:240'],
            'level' => ['sometimes', 'required', Rule::enum(WorkoutLevel::class)],
            'image' => ['sometimes', 'nullable', 'image', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return (new StoreWorkoutRequest)->messages();
    }

    public function attributes(): array
    {
        return (new StoreWorkoutRequest)->attributes();
    }
}
