<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asistencia extends Model
{
    protected $table = 'asistencias';

    protected $primaryKey = 'id_asistencia';

    protected $fillable = [
        'id_horario',
        'id_matricula',
        'fecha',
        'estado',
        'origen',
        'id_usuario_registro',
        'id_registro_ingreso',
        'hora_registro',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function horario(): BelongsTo
    {
        return $this->belongsTo(Horario::class, 'id_horario', 'id_horario');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario_registro', 'id_usuario');
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class, 'id_matricula', 'id_matricula');
    }
}
