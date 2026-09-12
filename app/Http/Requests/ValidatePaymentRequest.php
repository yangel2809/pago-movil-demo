<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'size:6'],
            'payer_phone' => ['required', 'string', 'regex:/^04\d{9}$/'],
            'bank_code' => ['required', 'string', 'size:4'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'reference.size' => 'La referencia debe tener exactamente 6 dígitos.',
            'payer_phone.regex' => 'El teléfono debe tener el formato venezolano (ej. 04141234567).',
        ];
    }
}
