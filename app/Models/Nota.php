<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Nota extends Model
{
    protected $table = 'notas';

    protected $primaryKey = 'id_nota';

    protected $fillable = ['id_evaluacion', 'id_matricula', 'nota', 'observacion', 'id_usuario'];

    protected function casts(): array
    {
        return [
            'nota' => 'decimal:2',
        ];
    }

    public function evaluacion(): BelongsTo
    {
        return $this->belongsTo(Evaluacion::class, 'id_evaluacion', 'id_evaluacion');
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class, 'id_matricula', 'id_matricula');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }
}
