<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultadoCurso extends Model
{
    protected $table = 'resultados_curso';

    protected $primaryKey = 'id_resultado_curso';

    protected $fillable = ['id_resultado', 'id_curso', 'correctas', 'incorrectas', 'en_blanco', 'puntaje'];

    protected function casts(): array
    {
        return [
            'puntaje' => 'decimal:2',
        ];
    }

    public function resultadossimulacro(): BelongsTo
    {
        return $this->belongsTo(ResultadosSimulacro::class, 'id_resultado', 'id_resultado');
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'id_curso', 'id_curso');
    }
}
