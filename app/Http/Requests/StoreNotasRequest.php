<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreNotasRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'notas' => ['required', 'array', 'min:1', 'max:200'],
            'notas.*.id_matricula' => ['required', 'integer', 'distinct', 'exists:matriculas,id_matricula'],
            'notas.*.nota' => ['nullable', 'numeric', 'min:0'],
            'notas.*.observacion' => ['nullable', 'string', 'max:200'],
        ];
    }
}
