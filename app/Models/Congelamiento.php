<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Congelamiento extends Model
{
    protected $table = 'congelamientos';

    protected $primaryKey = 'id_congelamiento';

    protected $fillable = ['id_matricula', 'fecha_inicio', 'fecha_fin', 'motivo', 'monto_a_favor'];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'monto_a_favor' => 'decimal:2',
        ];
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class, 'id_matricula', 'id_matricula');
    }
}
