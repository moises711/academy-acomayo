<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Beca extends Model
{
    protected $table = 'becas';

    protected $primaryKey = 'id_beca';

    protected $fillable = ['nombre', 'tipo', 'porcentaje_descuento', 'activa'];

    protected function casts(): array
    {
        return [
            'porcentaje_descuento' => 'decimal:2',
            'activa' => 'boolean',
        ];
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class, 'id_beca', 'id_beca');
    }
}
