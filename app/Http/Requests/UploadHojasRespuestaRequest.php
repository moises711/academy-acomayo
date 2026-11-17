<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadHojasRespuestaRequest extends FormRequest
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
            'id_simulacro' => ['required', 'integer', 'exists:simulacros,id_simulacro'],
            'id_plantilla' => ['required', 'integer', 'exists:plantillas_hoja,id_plantilla'],
            'id_matricula' => ['nullable', 'integer', 'exists:matriculas,id_matricula'],
            'lecturas' => ['nullable', 'array', 'max:20'],
            'lecturas.*' => ['nullable', 'array'],
            'lecturas.*.dni' => ['nullable', 'regex:/^[0-9]{8}$/'],
            'lecturas.*.grupo' => ['nullable', 'in:A,B,C,D'],
            'lecturas.*.respuestas' => ['required_with:lecturas.*.dni', 'array', 'max:120'],
            'lecturas.*.respuestas.*.numero_pregunta' => ['required', 'integer', 'min:1', 'max:120'],
            'lecturas.*.respuestas.*.marcada' => ['nullable', 'in:A,B,C,D,E'],
            'lecturas.*.respuestas.*.estado' => ['required', 'in:valida,blanco,multiple,dudosa'],
            'archivos' => ['required', 'array', 'min:1', 'max:20'],
            'archivos.*' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ];
    }
}
