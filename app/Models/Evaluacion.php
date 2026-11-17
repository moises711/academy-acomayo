<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluacion extends Model
{
    protected $table = 'evaluaciones';

    protected $primaryKey = 'id_evaluacion';

    protected $fillable = ['id_grupo', 'id_curso', 'titulo', 'tipo', 'fecha', 'nota_maxima', 'peso'];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'nota_maxima' => 'decimal:2',
            'peso' => 'decimal:2',
        ];
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'id_grupo', 'id_grupo');
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'id_curso', 'id_curso');
    }

    public function notas(): HasMany
    {
        return $this->hasMany(Nota::class, 'id_evaluacion', 'id_evaluacion');
    }
}
