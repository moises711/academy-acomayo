<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMatriculaRequest extends FormRequest
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
            'id_estudiante' => [
                'sometimes',
                'required',
                'exists:estudiantes,id_estudiante',
                Rule::unique('matriculas')
                    ->where(fn ($query) => $query->where('id_ciclo', $this->input('id_ciclo', $this->route('matricula')?->id_ciclo)))
                    ->ignore($this->route('matricula')?->id_matricula, 'id_matricula'),
            ],
            'id_ciclo' => ['sometimes', 'required', 'exists:ciclos,id_ciclo'],
            'id_grupo' => ['nullable', 'exists:grupos,id_grupo'],
            'id_beca' => ['nullable', 'exists:becas,id_beca'],
            'id_carrera_objetivo' => ['nullable', 'exists:carreras,id_carrera'],
            'fecha_matricula' => ['sometimes', 'required', 'date'],
            'fecha_retiro' => ['nullable', 'date', 'after_or_equal:fecha_matricula'],
            'estado_matricula' => ['sometimes', 'required', Rule::in(['inscrito', 'retirado', 'congelado', 'finalizado'])],
        ];
    }
}
