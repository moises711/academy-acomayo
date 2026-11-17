<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHojaRespuestaRequest extends FormRequest
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
            'id_matricula' => ['nullable', 'integer', 'exists:matriculas,id_matricula'],
            'dni_postulante' => ['nullable', 'string', 'regex:/^[0-9]{8}$/'],
            'motivo' => ['required', 'string', 'max:200'],
            'respuestas' => ['sometimes', 'array', 'max:120'],
            'respuestas.*.numero_pregunta' => ['required', 'integer', 'min:1', 'distinct'],
            'respuestas.*.marcada' => ['nullable', Rule::in(['A', 'B', 'C', 'D', 'E'])],
            'respuestas.*.estado' => ['required', Rule::in(['valida', 'blanco', 'multiple', 'dudosa'])],
        ];
    }
}
