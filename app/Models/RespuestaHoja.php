<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RespuestaHoja extends Model
{
    protected $table = 'respuestas_hoja';

    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['id_hoja', 'numero_pregunta', 'marcada', 'estado'];
}
