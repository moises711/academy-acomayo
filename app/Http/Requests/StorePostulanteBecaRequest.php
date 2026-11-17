<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePostulanteBecaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_convocatoria' => ['required', 'integer', 'exists:convocatorias_becas,id_convocatoria'],
            'dni' => ['required', 'string', 'regex:/^[0-9]{8}$/'],
            'telefono' => ['required', 'string', 'regex:/^9[0-9]{8}$/'],
            'grupo' => ['required', Rule::in(['A', 'B', 'C', 'D'])],
        ];
    }

    public function messages(): array
    {
        return ['dni.regex' => 'El DNI debe tener 8 dígitos.', 'telefono.regex' => 'El celular debe tener 9 dígitos y empezar con 9.', 'grupo.in' => 'Selecciona el grupo A, B, C o D.'];
    }
}
