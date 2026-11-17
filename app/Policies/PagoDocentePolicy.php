<?php

namespace App\Policies;

use App\Models\PagoDocente;
use App\Models\User;

class PagoDocentePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('pagos_docentes.gestionar');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PagoDocente $pagoDocente): bool
    {
        return $user->hasPermissionTo('pagos_docentes.gestionar');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('pagos_docentes.gestionar');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PagoDocente $pagoDocente): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PagoDocente $pagoDocente): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PagoDocente $pagoDocente): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PagoDocente $pagoDocente): bool
    {
        return false;
    }

    public function annul(User $user, PagoDocente $pagoDocente): bool
    {
        return $user->hasPermissionTo('pagos_docentes.gestionar');
    }
}
