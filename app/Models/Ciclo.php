<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ciclo extends Model
{
    protected $table = 'ciclos';

    protected $primaryKey = 'id_ciclo';

    protected $fillable = [
        'nombre_ciclo',
        'tipo_ciclo',
        'fecha_inicio',
        'fecha_fin',
        'modalidad',
        'estado',
    ];

    public function getRouteKeyName(): string
    {
        return 'id_ciclo';
    }

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function pagosDocentes(): HasMany
    {
        return $this->hasMany(PagoDocente::class, 'id_ciclo', 'id_ciclo');
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class, 'id_ciclo');
    }

    public function simulacros(): HasMany
    {
        return $this->hasMany(Simulacro::class, 'id_ciclo');
    }

    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class, 'id_ciclo', 'id_ciclo');
    }
}
