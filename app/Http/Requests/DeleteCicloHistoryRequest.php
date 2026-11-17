<?php

namespace App\Http\Requests;

use App\Models\Ciclo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteCicloHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageHistory', Ciclo::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'nombre_ciclo' => ['required', 'string', Rule::in([$this->route('ciclo')->nombre_ciclo])],
            'respaldo_verificado' => ['accepted'],
            'token_respaldo' => ['required', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_ciclo.required' => 'Escribe el nombre completo del ciclo.',
            'nombre_ciclo.in' => 'El nombre debe coincidir exactamente con el ciclo seleccionado.',
            'respaldo_verificado.accepted' => 'Confirma que guardaste y revisaste el respaldo descargado.',
            'token_respaldo.required' => 'Descarga el respaldo antes de eliminar.',
            'token_respaldo.uuid' => 'El respaldo no es válido. Descárgalo nuevamente.',
        ];
    }
}
