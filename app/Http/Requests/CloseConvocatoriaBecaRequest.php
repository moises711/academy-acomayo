<?php

namespace App\Http\Requests;

use App\Models\ConvocatoriaBeca;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CloseConvocatoriaBecaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', ConvocatoriaBeca::class) ?? false;
    }

    public function rules(): array
    {
        return ['confirmacion' => ['required', Rule::in([$this->route('convocatoria')->nombre])], 'eliminar_no_becarios' => ['accepted']];
    }

    public function messages(): array
    {
        return ['confirmacion.in' => 'Escribe exactamente el nombre de la convocatoria.', 'eliminar_no_becarios.accepted' => 'Confirma la eliminación de postulantes que no alcanzaron el puntaje.'];
    }
}
