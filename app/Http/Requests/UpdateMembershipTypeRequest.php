<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMembershipTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Правило unique игнорирует текущую запись, иначе её нельзя сохранить без смены названия.
            'name' => [
                'sometimes', 'required', 'string', 'max:100',
                Rule::unique('membership_types', 'name')->ignore($this->route('membership_type')),
            ],
            'price' => ['sometimes', 'required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'duration_days' => ['sometimes', 'required', 'integer', 'min:1', 'max:3650'],
            'visits_limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:65535'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return (new StoreMembershipTypeRequest)->messages();
    }

    public function attributes(): array
    {
        return (new StoreMembershipTypeRequest)->attributes();
    }
}
