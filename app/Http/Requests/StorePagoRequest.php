<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_matricula' => ['required', 'integer', 'exists:matriculas,id_matricula'],
            'monto' => ['required', 'decimal:0,2', 'min:0.01'],
            'metodo_pago' => ['required', Rule::in(['Efectivo', 'Transferencia', 'Tarjeta', 'Yape/Plin'])],
            'tipo_comprobante' => ['nullable', Rule::in(['Boleta', 'Factura', 'Recibo'])],
            'nro_comprobante' => ['nullable', 'string', 'max:30', 'unique:pagos,nro_comprobante'],
            'aplicaciones' => ['required', 'array', 'min:1'],
            'aplicaciones.*.id_cuota' => ['required', 'integer', 'distinct', 'exists:cuotas,id_cuota'],
            'aplicaciones.*.monto_aplicado' => ['required', 'decimal:0,2', 'min:0.01'],
        ];
    }
}
