<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $table = 'permisos';

    protected $primaryKey = 'id_permiso';

    protected $fillable = ['codigo', 'modulo', 'descripcion'];

    public $timestamps = false;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'rol_permiso', 'id_permiso', 'id_rol');
    }

    public function getRouteKeyName(): string
    {
        return 'id_permiso';
    }
}
