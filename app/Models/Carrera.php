<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrera extends Model
{
    protected $table = 'carreras';

    protected $primaryKey = 'id_carrera';

    protected $fillable = ['id_universidad', 'id_area', 'nombre', 'puntaje_minimo'];

    protected function casts(): array
    {
        return [
            'puntaje_minimo' => 'decimal:2',
        ];
    }

    public function universidad(): BelongsTo
    {
        return $this->belongsTo(Universidad::class, 'id_universidad', 'id_universidad');
    }

    public function areaacademica(): BelongsTo
    {
        return $this->belongsTo(AreaAcademica::class, 'id_area', 'id_area');
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class, 'id_carrera_objetivo', 'id_carrera');
    }
}
