<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlantillaHojaRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:100'],
            'titulo' => ['nullable', 'string', 'max:100'],
            'instrucciones' => ['nullable', 'string', 'max:180'],
            'diseno' => ['nullable', 'in:clasico,compacto'],
            'opciones_por_pregunta' => ['nullable', 'integer', 'in:4,5'],
            'columnas' => ['nullable', 'integer', 'in:1,2'],
        ];
    }
}
