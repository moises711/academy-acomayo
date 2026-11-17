<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Material extends Model
{
    protected $table = 'materiales';

    protected $primaryKey = 'id_material';

    protected $fillable = ['id_curso', 'id_grupo', 'id_usuario', 'titulo', 'tipo', 'url'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'id_curso', 'id_curso');
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
