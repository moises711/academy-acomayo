<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Anuncio extends Model
{
    protected $table = 'anuncios';

    protected $primaryKey = 'id_anuncio';

    protected $fillable = ['id_ciclo', 'id_grupo', 'id_usuario', 'titulo', 'contenido', 'publicado_en', 'vence_en'];

    protected function casts(): array
    {
        return [
            'publicado_en' => 'datetime',
            'vence_en' => 'datetime',
        ];
    }

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(Ciclo::class, 'id_ciclo', 'id_ciclo');
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'id_grupo', 'id_grupo');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }
}
