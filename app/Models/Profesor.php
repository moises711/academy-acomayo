<?php

namespace App\Models;

use Database\Factories\ProfesorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Profesor extends Model
{
    /** @use HasFactory<ProfesorFactory> */
    use HasFactory;

    protected $table = 'profesores';

    protected $primaryKey = 'id_profesor';

    protected $fillable = ['dni', 'nombre', 'apellido', 'email', 'telefono', 'estado'];

    public function cursos(): BelongsToMany
    {
        return $this->belongsToMany(Curso::class, 'profesor_cursos', 'id_profesor', 'id_curso');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class, 'id_profesor', 'id_profesor');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id_profesor', 'id_profesor');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoDocente::class, 'id_profesor', 'id_profesor');
    }
}
