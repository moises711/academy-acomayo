<?php

namespace App\Policies;

use App\Models\User;

class CicloPolicy
{
    public function manageHistory(User $user): bool
    {
        return $user->hasPermissionTo('ciclos.historial');
    }
}
