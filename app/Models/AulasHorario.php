<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AulasHorario extends Model
{
    protected $table = 'aulas_horarios';

    protected $primaryKey = 'id_horario';

    protected $fillable = [
        'id_curso',
        'id_ciclo',
        'aula',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
    ];

    public function getRouteKeyName(): string
    {
        return 'id_horario';
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'id_curso');
    }

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(Ciclo::class, 'id_ciclo');
    }
}
