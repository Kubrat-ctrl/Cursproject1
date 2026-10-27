<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Покупка абонемента клиентом: передаётся только тип. */
class PurchaseMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'membership_type_id' => [
                'required', 'integer',
                Rule::exists('membership_types', 'id')->where('is_active', true),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'membership_type_id.required' => 'Укажите тип абонемента.',
            'membership_type_id.integer' => 'Идентификатор типа абонемента должен быть целым числом.',
            'membership_type_id.exists' => 'Такого типа абонемента нет или он снят с продажи.',
        ];
    }
}
