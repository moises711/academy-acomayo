<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Apoderado extends Model
{
    protected $table = 'apoderados';

    protected $primaryKey = 'id_apoderado';

    protected $fillable = ['id_estudiante', 'nombre', 'apellido', 'parentesco', 'telefono', 'email'];

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'id_estudiante', 'id_estudiante');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id_apoderado', 'id_apoderado');
    }
}
