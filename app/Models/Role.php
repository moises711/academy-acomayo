<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $primaryKey = 'id_rol';

    protected $fillable = ['nombre', 'descripcion'];

    public $timestamps = false;

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_rol', 'id_rol');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'rol_permiso', 'id_rol', 'id_permiso');
    }

    public function getRouteKeyName(): string
    {
        return 'id_rol';
    }
}
