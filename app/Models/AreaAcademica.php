<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AreaAcademica extends Model
{
    protected $table = 'areas_academicas';

    protected $primaryKey = 'id_area';

    protected $fillable = ['nombre'];

    public function carreras(): HasMany
    {
        return $this->hasMany(Carrera::class, 'id_area', 'id_area');
    }

    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class, 'id_area', 'id_area');
    }
}
