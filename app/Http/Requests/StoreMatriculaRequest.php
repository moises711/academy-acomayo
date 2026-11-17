<?php

namespace App\Http\Requests;

use App\Models\Beca;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMatriculaRequest extends FormRequest
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
            'id_estudiante' => ['required', 'exists:estudiantes,id_estudiante', Rule::unique('matriculas')->where('id_ciclo', $this->input('id_ciclo'))],
            'id_ciclo' => ['required', 'exists:ciclos,id_ciclo'],
            'id_grupo' => ['nullable', Rule::exists('grupos', 'id_grupo')->where('id_ciclo', $this->input('id_ciclo'))],
            'id_beca' => ['nullable', 'exists:becas,id_beca'],
            'id_carrera_objetivo' => ['nullable', 'exists:carreras,id_carrera'],
            'fecha_matricula' => ['required', 'date'],
            'plan.monto_total' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'plan.numero_cuotas' => ['required', 'integer', 'between:1,24'],
            'plan.fecha_primera_cuota' => ['required', 'date'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->filled('id_beca') && ! Beca::query()->whereKey($this->input('id_beca'))->where('activa', true)->exists()) {
                $validator->errors()->add('id_beca', 'La beca seleccionada ya no está activa.');
            }
        }];
    }
}
