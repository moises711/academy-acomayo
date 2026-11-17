<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClavesRespuestaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'claves' => ['required', 'array', 'min:1', 'max:120'],
            'claves.*.numero_pregunta' => ['required', 'integer', 'min:1', 'distinct'],
            'claves.*.id_curso' => ['required', 'integer', 'exists:cursos,id_curso'],
            'claves.*.respuesta_correcta' => ['required', Rule::in(['A', 'B', 'C', 'D', 'E'])],
            'claves.*.puntos_correcta' => ['required', 'decimal:0,2', 'min:0'],
            'claves.*.puntos_incorrecta' => ['required', 'decimal:0,2', 'between:-100,0'],
        ];
    }
}
