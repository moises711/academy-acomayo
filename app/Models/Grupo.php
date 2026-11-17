<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grupo extends Model
{
    protected $table = 'grupos';

    protected $primaryKey = 'id_grupo';

    protected $fillable = ['id_ciclo', 'id_area', 'nombre', 'turno', 'capacidad'];

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(Ciclo::class, 'id_ciclo', 'id_ciclo');
    }

    public function areaacademica(): BelongsTo
    {
        return $this->belongsTo(AreaAcademica::class, 'id_area', 'id_area');
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class, 'id_grupo', 'id_grupo');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class, 'id_grupo', 'id_grupo');
    }

    public function evaluaciones(): HasMany
    {
        return $this->hasMany(Evaluacion::class, 'id_grupo', 'id_grupo');
    }
}
