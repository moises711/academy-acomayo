<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultadosSimulacro extends Model
{
    protected $table = 'resultados_simulacros';

    protected $primaryKey = 'id_resultado';

    protected $fillable = [
        'id_simulacro',
        'id_matricula',
        'puntaje_obtenido',
        'puesto_general',
        'puesto_carrera',
        'es_rezagado',
        'fecha_rendicion',
        'estado_ranking',
    ];

    public function getRouteKeyName(): string
    {
        return 'id_resultado';
    }

    protected function casts(): array
    {
        return [
            'puntaje_obtenido' => 'decimal:2',
            'es_rezagado' => 'boolean',
            'fecha_rendicion' => 'date',
        ];
    }

    public function simulacro(): BelongsTo
    {
        return $this->belongsTo(Simulacro::class, 'id_simulacro');
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class, 'id_matricula', 'id_matricula');
    }
}
