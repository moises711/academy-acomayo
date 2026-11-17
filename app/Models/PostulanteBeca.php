<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PostulanteBeca extends Model
{
    use HasFactory;

    protected $table = 'postulantes_becas';

    protected $primaryKey = 'id_postulante';

    protected $fillable = ['id_convocatoria', 'dni', 'telefono', 'grupo', 'nota', 'estado'];

    protected function casts(): array
    {
        return ['nota' => 'decimal:2'];
    }

    public function convocatoria(): BelongsTo
    {
        return $this->belongsTo(ConvocatoriaBeca::class, 'id_convocatoria', 'id_convocatoria');
    }

    public function hoja(): HasOne
    {
        return $this->hasOne(HojaRespuesta::class, 'id_postulante', 'id_postulante');
    }

    public function examenId(): ?int
    {
        return in_array($this->grupo, ['A', 'B'], true) ? $this->convocatoria->id_simulacro_ab : $this->convocatoria->id_simulacro_cd;
    }
}
