<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Aula extends Model
{
    protected $table = 'aulas';

    protected $primaryKey = 'id_aula';

    protected $fillable = ['nombre', 'tipo', 'capacidad', 'enlace_virtual'];

    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class, 'id_aula', 'id_aula');
    }
}
