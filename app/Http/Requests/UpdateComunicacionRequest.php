<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateComunicacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('comunicacion.gestionar') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'tipo_registro' => ['required', Rule::in(['anuncio', 'material'])],
            'titulo' => ['required', 'string', 'max:150'],
            'contenido' => ['required_if:tipo_registro,anuncio', 'nullable', 'string', 'max:10000'],
            'id_ciclo' => ['nullable', 'integer', 'exists:ciclos,id_ciclo'],
            'id_grupo' => ['nullable', 'integer', 'exists:grupos,id_grupo'],
            'id_curso' => ['required_if:tipo_registro,material', 'nullable', 'integer', 'exists:cursos,id_curso'],
            'tipo' => ['required_if:tipo_registro,material', 'nullable', Rule::in(['PDF', 'Video', 'Enlace', 'Otro'])],
            'url' => ['required_if:tipo_registro,material', 'nullable', 'url', 'max:500'],
            'vence_en' => ['nullable', 'date', 'after:now'],
        ];
    }
}
