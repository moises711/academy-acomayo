<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveCarnetTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('carnets.disenar') ?? false;
    }

    /** @return array<string, array<int, mixed>|string> */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:80'],
            'ancho_mm' => ['required', 'numeric', 'between:30,220'],
            'alto_mm' => ['required', 'numeric', 'between:30,220'],
            'orientacion' => ['required', 'in:horizontal,vertical'],
            'campos' => ['required', 'array:nombre,dni,codigo,grupo,ciclo,vigencia,foto,logo,nombre_academia,texto_libre'],
            'campos.nombre' => ['required', 'boolean'],
            'campos.dni' => ['required', 'boolean'],
            'campos.codigo' => ['required', 'boolean'],
            'campos.grupo' => ['required', 'boolean'],
            'campos.ciclo' => ['required', 'boolean'],
            'campos.vigencia' => ['required', 'boolean'],
            'campos.foto' => ['required', 'boolean'],
            'campos.logo' => ['required', 'boolean'],
            'campos.nombre_academia' => ['required', 'boolean'],
            'campos.texto_libre' => ['required', 'boolean'],
            'texto_carnet' => ['nullable', 'string', 'max:100'],
            'qr_mm' => ['required', 'numeric', 'between:10,60'],
            'qr_posicion' => ['required', 'in:izquierda,derecha,centro,esquina'],
            'color_fondo' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'color_acento' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'color_texto' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'tamano_fuente' => ['required', 'integer', 'between:8,18'],
            'franja_superior' => ['required', 'boolean'],
            'reverso' => ['required', 'boolean'],
            'texto_reverso' => ['nullable', 'string', 'max:500'],
        ];
    }
}
