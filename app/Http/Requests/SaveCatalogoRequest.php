<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCatalogoRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $catalogId = $this->route('id');

        return match ($this->route('tipo')) {
            'universidades' => [
                'nombre' => ['required', 'string', 'max:150', Rule::unique('universidades', 'nombre')->ignore($catalogId, 'id_universidad')],
                'siglas' => ['nullable', 'string', 'max:20'],
                'tipo' => ['nullable', Rule::in(['Pública', 'Privada'])],
            ],
            'areas' => ['nombre' => ['required', 'string', 'max:80', Rule::unique('areas_academicas', 'nombre')->ignore($catalogId, 'id_area')]],
            'carreras' => [
                'id_universidad' => ['required', 'integer', 'exists:universidades,id_universidad'],
                'id_area' => ['required', 'integer', 'exists:areas_academicas,id_area'],
                'nombre' => [
                    'required', 'string', 'max:150',
                    Rule::unique('carreras', 'nombre')->where('id_universidad', $this->input('id_universidad'))->ignore($catalogId, 'id_carrera'),
                ],
                'puntaje_minimo' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            ],
            'becas' => [
                'nombre' => ['required', 'string', 'max:80'],
                'tipo' => ['required', Rule::in(['Beca', 'Media Beca', 'Convenio', 'Otro'])],
                'porcentaje_descuento' => ['required', 'numeric', 'min:0', 'max:100'],
                'activa' => ['required', 'boolean'],
            ],
            'aulas' => [
                'nombre' => ['required', 'string', 'max:50', Rule::unique('aulas', 'nombre')->ignore($catalogId, 'id_aula')],
                'tipo' => ['required', Rule::in(['Presencial', 'Virtual'])],
                'capacidad' => ['nullable', 'integer', 'min:1', 'max:1000'],
                'enlace_virtual' => ['nullable', 'url', 'max:255'],
            ],
            'grupos' => [
                'id_ciclo' => ['required', 'integer', 'exists:ciclos,id_ciclo'],
                'id_area' => ['nullable', 'integer', 'exists:areas_academicas,id_area'],
                'nombre' => [
                    'required', 'string', 'max:50',
                    Rule::unique('grupos', 'nombre')->where('id_ciclo', $this->input('id_ciclo'))->ignore($catalogId, 'id_grupo'),
                ],
                'turno' => ['required', Rule::in(['Mañana', 'Tarde', 'Noche'])],
                'capacidad' => ['nullable', 'integer', 'min:1', 'max:1000'],
            ],
            default => [],
        };
    }
}
