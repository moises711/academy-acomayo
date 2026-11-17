<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaveRespuesta extends Model
{
    protected $table = 'claves_respuesta';

    protected $primaryKey = 'id_clave';

    protected $fillable = ['id_simulacro', 'id_curso', 'numero_pregunta', 'respuesta_correcta', 'puntos_correcta', 'puntos_incorrecta'];

    protected function casts(): array
    {
        return [
            'puntos_correcta' => 'decimal:2',
            'puntos_incorrecta' => 'decimal:2',
        ];
    }

    public function simulacro(): BelongsTo
    {
        return $this->belongsTo(Simulacro::class, 'id_simulacro', 'id_simulacro');
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'id_curso', 'id_curso');
    }
}
