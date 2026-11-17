<?php

namespace App\Http\Requests;

use App\Models\PagoDocente;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AnularPagoDocenteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $pagoDocente = $this->route('pagoDocente');

        return $pagoDocente instanceof PagoDocente
            && ($this->user()?->can('annul', $pagoDocente) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'motivo_anulacion' => ['required', 'string', 'max:200'],
        ];
    }
}
