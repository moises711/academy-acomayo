<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $primaryKey = 'id_usuario';

    protected $fillable = [
        'name', 'email', 'password', 'id_rol', 'id_profesor', 'id_estudiante',
        'id_apoderado', 'activo', 'email_verified_at', 'remember_token',
    ];

    protected $appends = ['role'];

    public function roleDefinition(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'id_rol', 'id_rol');
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'id_estudiante', 'id_estudiante');
    }

    public function apoderado(): BelongsTo
    {
        return $this->belongsTo(Apoderado::class, 'id_apoderado', 'id_apoderado');
    }

    public function profesor(): BelongsTo
    {
        return $this->belongsTo(Profesor::class, 'id_profesor', 'id_profesor');
    }

    public function pagosDocente(): HasMany
    {
        return $this->hasMany(PagoDocente::class, 'id_usuario', 'id_usuario');
    }

    public function getRoleAttribute(): ?string
    {
        return $this->roleDefinition?->nombre;
    }

    public function hasPermissionTo(string $permission): bool
    {
        return $this->roleDefinition()
            ->whereHas('permissions', fn ($query) => $query->where('codigo', $permission))
            ->exists();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
