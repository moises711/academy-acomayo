<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Estudiante extends Model
{
    protected $table = 'estudiantes';

    protected $primaryKey = 'id_estudiante';

    protected $hidden = ['codigo_qr', 'foto_path'];

    protected $fillable = [
        'dni',
        'nombre',
        'apellido',
        'email',
        'telefono',
        'fecha_nacimiento',
        'direccion',
        'foto_path',
        'colegio_procedencia',
        'anio_egreso',
        'estado',
    ];

    public function getRouteKeyName(): string
    {
        return 'id_estudiante';
    }

    protected static function booted(): void
    {
        static::creating(function (Estudiante $estudiante): void {
            $estudiante->codigo_qr ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'qr_regenerado_at' => 'datetime',
        ];
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class, 'id_estudiante');
    }

    public function apoderados(): HasMany
    {
        return $this->hasMany(Apoderado::class, 'id_estudiante', 'id_estudiante');
    }

    public function resultadosSimulacros(): HasManyThrough
    {
        return $this->hasManyThrough(ResultadosSimulacro::class, Matricula::class, 'id_estudiante', 'id_matricula', 'id_estudiante', 'id_matricula');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id_estudiante', 'id_estudiante');
    }

    #[Scope]
    protected function activo(Builder $query): Builder
    {
        return $query->where('estado', 'activo');
    }
}
