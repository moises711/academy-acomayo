<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEstudianteRequest extends FormRequest
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
            'dni' => ['sometimes', 'required', 'string', 'max:15', 'regex:/^\d{8,15}$/', Rule::unique('estudiantes', 'dni')->ignore($this->route('estudiante')?->id_estudiante, 'id_estudiante')],
            'nombre' => ['sometimes', 'required', 'string', 'max:80'],
            'apellido' => ['sometimes', 'required', 'string', 'max:80'],
            'email' => ['sometimes', 'nullable', 'email', 'max:120', Rule::unique('estudiantes', 'email')->ignore($this->route('estudiante')?->id_estudiante, 'id_estudiante')],
            'telefono' => ['nullable', 'string', 'max:20'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'colegio_procedencia' => ['nullable', 'string', 'max:150'],
            'anio_egreso' => ['nullable', 'integer', 'between:1990,'.(now()->year + 1)],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'estado' => ['sometimes', 'required', Rule::in(['activo', 'inactivo', 'egresado'])],
        ];
    }
}
