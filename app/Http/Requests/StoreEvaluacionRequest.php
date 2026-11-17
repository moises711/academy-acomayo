<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvaluacionRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'id_grupo' => ['required', 'integer', 'exists:grupos,id_grupo'],
            'id_curso' => ['required', 'integer', 'exists:cursos,id_curso'],
            'titulo' => ['required', 'string', 'max:150'],
            'tipo' => ['required', Rule::in(['Práctica', 'Examen', 'Tarea', 'Otro'])],
            'fecha' => ['required', 'date'],
            'nota_maxima' => ['required', 'numeric', 'gt:0', 'max:1000'],
            'peso' => ['required', 'numeric', 'gt:0', 'max:100'],
        ];
    }
}
