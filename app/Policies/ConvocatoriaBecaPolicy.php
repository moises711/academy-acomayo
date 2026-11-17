<?php

namespace App\Policies;

use App\Models\User;

class ConvocatoriaBecaPolicy
{
    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('usuarios.gestionar');
    }
}
