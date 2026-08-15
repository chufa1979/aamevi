<?php

namespace App\Policies;

use App\Models\User;

/**
 * Las cuentas son cosa del administrador.
 *
 * El panel de profesores no registra ni UserResource ni StudentResource, así que
 * esto no cambia nada de lo que hoy se ve; está para que sumar una pantalla que
 * toque usuarios no abra la puerta por descuido. Un docente ve a sus alumnos
 * desde el curso, que es donde tienen contexto.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
