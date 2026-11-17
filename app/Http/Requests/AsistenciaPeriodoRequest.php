<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AsistenciaPeriodoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('asistencia.ver') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['desde', 'hasta'])) {
                return;
            }

            $desde = $this->input('desde');
            $hasta = $this->input('hasta');

            if ($desde && $hasta && $hasta < $desde) {
                $validator->errors()->add('hasta', 'La fecha final debe ser igual o posterior a la fecha inicial.');
            }
        }];
    }

    /** @return array{0: string, 1: string} */
    public function rango(): array
    {
        $desde = $this->validated('desde') ?? $this->validated('hasta') ?? today()->toDateString();
        $hasta = $this->validated('hasta') ?? $desde;

        return [$desde, $hasta];
    }
}
