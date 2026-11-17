<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curso extends Model
{
    protected $table = 'cursos';

    protected $primaryKey = 'id_curso';

    protected $fillable = [
        'nombre_curso',
        'categoria',
    ];

    public function getRouteKeyName(): string
    {
        return 'id_curso';
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class, 'id_curso', 'id_curso');
    }

    public function profesores(): BelongsToMany
    {
        return $this->belongsToMany(Profesor::class, 'profesor_cursos', 'id_curso', 'id_profesor');
    }
}
