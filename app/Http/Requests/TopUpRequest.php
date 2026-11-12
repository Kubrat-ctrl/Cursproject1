<?php

namespace App\Http\Requests;

use App\Services\WalletService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TopUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('card_number')) {
            $this->merge(['card_number' => preg_replace('/\D/', '', (string) $this->input('card_number'))]);
        }
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:100', 'max:500000'],
            'method' => ['required', Rule::in(WalletService::METHODS)],
            'card_number' => ['required_if:method,card', 'nullable', 'regex:/^\d{13,19}$/'],
            'card_expiry' => ['required_if:method,card', 'nullable', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'],
            'card_cvc' => ['required_if:method,card', 'nullable', 'digits_between:3,4'],
        ];
    }
}