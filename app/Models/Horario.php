<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Horario extends Model
{
    protected $table = 'horarios';

    protected $primaryKey = 'id_horario';

    protected $fillable = ['id_grupo', 'id_curso', 'id_profesor', 'id_aula', 'dia_semana', 'hora_inicio', 'hora_fin'];

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'id_grupo', 'id_grupo');
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'id_curso', 'id_curso');
    }

    public function profesor(): BelongsTo
    {
        return $this->belongsTo(Profesor::class, 'id_profesor', 'id_profesor');
    }

    public function aula(): BelongsTo
    {
        return $this->belongsTo(Aula::class, 'id_aula', 'id_aula');
    }
}
