<?php

namespace App\Http\Requests;

use App\Models\ConvocatoriaBeca;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveConvocatoriaBecaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', ConvocatoriaBeca::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'fecha_fin' => ['required', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
            'examen_becas_activo' => ['sometimes', 'boolean'],
            'fecha_examen' => [Rule::requiredIf($this->boolean('examen_becas_activo')), 'nullable', 'date_format:Y-m-d'],
            'hora_inicio_examen' => [Rule::requiredIf($this->boolean('examen_becas_activo')), 'nullable', 'date_format:H:i'],
            'hora_fin_examen' => [Rule::requiredIf($this->boolean('examen_becas_activo')), 'nullable', 'date_format:H:i', 'after:hora_inicio_examen'],
            'puntaje_minimo' => ['required', 'decimal:0,2', 'between:0,999999.99'],
            'estado' => ['required', Rule::in(['Borrador', 'Abierta'])],
            'modo_clave' => ['nullable', 'in:separada,unificada'],
            'id_simulacro_ab' => ['required', 'integer', 'exists:simulacros,id_simulacro', Rule::when($this->input('modo_clave') !== 'unificada', ['different:id_simulacro_cd'])],
            'id_simulacro_cd' => ['required_unless:modo_clave,unificada', 'nullable', 'integer', 'exists:simulacros,id_simulacro'],
        ];
    }

    public function messages(): array
    {
        return ['id_simulacro_ab.different' => 'Selecciona exámenes distintos para las claves A–B y C–D.', 'fecha_fin.after_or_equal' => 'El cierre no puede ser anterior al inicio.', 'hora_fin_examen.after' => 'La hora de fin debe ser posterior a la hora de inicio.'];
    }
}
