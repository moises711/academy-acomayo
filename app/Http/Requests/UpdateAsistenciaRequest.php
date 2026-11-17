<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAsistenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', 'in:Presente,Tarde,Falta,Justificada'],
            'motivo' => ['required', 'string', 'max:200'],
        ];
    }
}
