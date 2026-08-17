<?php

namespace App\Policies;

use App\Models\User;

/**
 * La cola de avisos es del administrador.
 *
 * Un aviso lleva el correo de una persona y el texto que se le mandó; el panel
 * de profesores no la registra, y esto lo sostiene si alguna vez se registrara.
 */
class QueuedEmailPolicy
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
        return false;
    }

    public function update(User $user): bool
    {
        return false;
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
