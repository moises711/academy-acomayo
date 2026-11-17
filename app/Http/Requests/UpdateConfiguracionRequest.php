<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateConfiguracionRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'tolerancia_minutos' => ['required', 'integer', 'min:0', 'max:60'],
            'bloquear_si_deuda' => ['required', 'boolean'],
        ];
    }
}
