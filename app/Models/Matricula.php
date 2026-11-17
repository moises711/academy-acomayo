<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Matricula extends Model
{
    protected $table = 'matriculas';

    protected $primaryKey = 'id_matricula';

    protected $fillable = [
        'id_estudiante',
        'id_ciclo',
        'id_grupo',
        'id_beca',
        'id_carrera_objetivo',
        'fecha_matricula',
        'fecha_retiro',
        'estado_matricula',
    ];

    public function getRouteKeyName(): string
    {
        return 'id_matricula';
    }

    protected function casts(): array
    {
        return [
            'fecha_matricula' => 'date',
            'fecha_retiro' => 'date',
        ];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'id_estudiante');
    }

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(Ciclo::class, 'id_ciclo');
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'id_grupo', 'id_grupo');
    }

    public function beca(): BelongsTo
    {
        return $this->belongsTo(Beca::class, 'id_beca', 'id_beca');
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'id_carrera_objetivo', 'id_carrera');
    }

    public function cuotas(): HasMany
    {
        return $this->hasMany(Cuota::class, 'id_matricula', 'id_matricula');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'id_matricula', 'id_matricula');
    }
}
