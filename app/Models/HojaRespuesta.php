<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HojaRespuesta extends Model
{
    protected $table = 'hojas_respuesta';

    protected $primaryKey = 'id_hoja';

    protected $fillable = ['id_simulacro', 'id_matricula', 'id_postulante', 'id_plantilla', 'codigo_hoja', 'archivo_path', 'estado', 'confianza', 'procesada_en', 'id_usuario'];

    protected function casts(): array
    {
        return [
            'confianza' => 'decimal:2',
            'procesada_en' => 'datetime',
        ];
    }

    public function simulacro(): BelongsTo
    {
        return $this->belongsTo(Simulacro::class, 'id_simulacro', 'id_simulacro');
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class, 'id_matricula', 'id_matricula');
    }

    public function postulante(): BelongsTo
    {
        return $this->belongsTo(PostulanteBeca::class, 'id_postulante', 'id_postulante');
    }

    public function plantillahoja(): BelongsTo
    {
        return $this->belongsTo(PlantillaHoja::class, 'id_plantilla', 'id_plantilla');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(RespuestaHoja::class, 'id_hoja', 'id_hoja');
    }
}
