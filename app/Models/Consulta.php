<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consulta extends Model
{
    protected $table = 'consultas';

    protected $primaryKey = 'id_consulta';

    protected $fillable = ['nombre', 'telefono', 'email', 'interes', 'mensaje', 'estado'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
