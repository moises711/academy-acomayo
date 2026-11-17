<?php

namespace App\Http\Requests;

use App\Models\Ciclo;
use Illuminate\Foundation\Http\FormRequest;

class AssignPagoDocenteCicloRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageHistory', Ciclo::class) ?? false;
    }

    public function rules(): array
    {
        return ['id_ciclo' => ['required', 'integer', 'exists:ciclos,id_ciclo']];
    }

    public function messages(): array
    {
        return ['id_ciclo.required' => 'Selecciona el ciclo al que corresponde el pago.', 'id_ciclo.exists' => 'El ciclo seleccionado ya no está disponible.'];
    }
}
