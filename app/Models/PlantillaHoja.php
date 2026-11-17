<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlantillaHoja extends Model
{
    protected $table = 'plantillas_hoja';

    protected $primaryKey = 'id_plantilla';

    protected $fillable = ['nombre', 'num_preguntas', 'opciones_por_pregunta', 'config', 'activa'];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'activa' => 'boolean',
        ];
    }
}
