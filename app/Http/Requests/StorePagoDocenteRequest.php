<?php

namespace App\Http\Requests;

use App\Models\PagoDocente;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePagoDocenteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', PagoDocente::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id_ciclo' => ['required', 'integer', 'exists:ciclos,id_ciclo'],
            'id_profesor' => ['required', 'integer', 'exists:profesores,id_profesor'],
            'monto' => ['required', 'numeric', 'gt:0', 'max:99999999.99', 'decimal:0,2'],
            'fecha_pago' => ['required', 'date', 'before_or_equal:today'],
            'periodo' => ['required', 'date_format:Y-m'],
            'metodo_pago' => ['required', Rule::in(['Efectivo', 'Transferencia', 'Tarjeta', 'Yape/Plin'])],
            'nro_comprobante' => ['nullable', 'string', 'max:30', Rule::unique('pagos_docentes', 'nro_comprobante')],
        ];
    }
}
