<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroIngreso extends Model
{
    protected $table = 'registros_ingreso';

    protected $primaryKey = 'id_registro';

    protected $fillable = ['id_matricula', 'id_usuario', 'tipo', 'metodo', 'fecha_hora', 'observacion'];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
        ];
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
