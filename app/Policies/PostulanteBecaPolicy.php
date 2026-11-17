<?php

namespace App\Policies;

use App\Models\User;

class PostulanteBecaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('usuarios.gestionar');
    }

    public function grade(User $user): bool
    {
        return $user->hasPermissionTo('omr.gestionar');
    }
}
