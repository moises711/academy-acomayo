<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    protected $table = 'auditoria';

    protected $primaryKey = 'id_auditoria';

    protected $fillable = ['id_usuario', 'accion', 'tabla', 'id_registro', 'datos_antes', 'datos_despues', 'ip'];

    protected function casts(): array
    {
        return [
            'datos_antes' => 'array',
            'datos_despues' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }
}
