<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCuotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_matricula' => ['required', 'integer', 'exists:matriculas,id_matricula'],
            'concepto' => ['required', 'string', 'max:100'],
            'numero_cuota' => ['nullable', 'integer', 'min:1'],
            'monto_original' => ['required', 'decimal:0,2', 'min:0.01'],
            'monto_descuento' => ['required', 'decimal:0,2', 'min:0', 'lte:monto_original'],
            'monto_final' => ['required', 'decimal:0,2', 'min:0'],
            'fecha_vencimiento' => ['required', 'date'],
        ];
    }
}
