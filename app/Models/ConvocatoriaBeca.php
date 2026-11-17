<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ConvocatoriaBeca extends Model
{
    use HasFactory;

    protected $table = 'convocatorias_becas';

    protected $primaryKey = 'id_convocatoria';

    protected $fillable = ['nombre', 'descripcion', 'fecha_inicio', 'fecha_fin', 'examen_becas_activo', 'fecha_examen', 'hora_inicio_examen', 'hora_fin_examen', 'puntaje_minimo', 'estado', 'id_simulacro_ab', 'id_simulacro_cd', 'depurada_en'];

    protected function casts(): array
    {
        return ['fecha_inicio' => 'date', 'fecha_fin' => 'date', 'examen_becas_activo' => 'boolean', 'fecha_examen' => 'date', 'puntaje_minimo' => 'decimal:2', 'depurada_en' => 'datetime'];
    }

    public function postulantes(): HasMany
    {
        return $this->hasMany(PostulanteBeca::class, 'id_convocatoria', 'id_convocatoria');
    }

    public function examenAb(): BelongsTo
    {
        return $this->belongsTo(Simulacro::class, 'id_simulacro_ab', 'id_simulacro');
    }

    public function examenCd(): BelongsTo
    {
        return $this->belongsTo(Simulacro::class, 'id_simulacro_cd', 'id_simulacro');
    }

    public function admiteInscripciones(): bool
    {
        return $this->estado === 'Abierta' && $this->fecha_inicio->lte(today('America/Lima')) && $this->fecha_fin->gte(today('America/Lima')) && $this->id_simulacro_ab && $this->id_simulacro_cd;
    }

    public function examenVisibleEnWeb(): bool
    {
        if (! $this->examen_becas_activo || ! $this->fecha_examen || ! $this->hora_inicio_examen || ! $this->hora_fin_examen || $this->estado !== 'Abierta' || ! $this->id_simulacro_ab || ! $this->id_simulacro_cd) {
            return false;
        }

        $examEndsAt = Carbon::parse($this->fecha_examen->toDateString().' '.$this->hora_fin_examen, 'America/Lima');

        return now('America/Lima')->lt($examEndsAt);
    }

    public static function examenPublicadoEnWeb(): ?self
    {
        return self::query()->where('estado', 'Abierta')->where('examen_becas_activo', true)
            ->whereDate('fecha_examen', '>=', today('America/Lima'))
            ->whereNotNull('hora_inicio_examen')->whereNotNull('hora_fin_examen')
            ->whereNotNull('id_simulacro_ab')->whereNotNull('id_simulacro_cd')
            ->orderBy('fecha_examen')->orderBy('hora_inicio_examen')->get()
            ->first(fn (self $campaign): bool => $campaign->examenVisibleEnWeb());
    }
}
