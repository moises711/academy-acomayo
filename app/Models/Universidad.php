<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Universidad extends Model
{
    protected $table = 'universidades';

    protected $primaryKey = 'id_universidad';

    protected $fillable = ['nombre', 'siglas', 'tipo'];

    public function carreras(): HasMany
    {
        return $this->hasMany(Carrera::class, 'id_universidad', 'id_universidad');
    }
}
