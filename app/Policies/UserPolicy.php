<?php

namespace App\Policies;

use App\Models\User;

/**
 * Las cuentas son cosa del administrador — con una excepción: el perfil
 * administrativo (`UserRole::Registrar`) también puede ver, crear y editar
 * fichas de **alumno**, porque es quien las da de alta en la práctica. No
 * llega a cuentas de docente o de otro administrador: `StudentResource`
 * (la única pantalla de usuarios que su panel registra) siempre crea con el
 * rol fijado a alumno, así que nunca hay un formulario ahí donde pudiera
 * elegir otro rol.
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
        return $user->isAdmin() || $user->isRegistrar();
    }

    public function view(User $user, User $record): bool
    {
        return $user->isAdmin() || ($user->isRegistrar() && $record->isStudent());
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isRegistrar();
    }

    public function update(User $user, User $record): bool
    {
        return $user->isAdmin() || ($user->isRegistrar() && $record->isStudent());
    }

    /** Borrar una cuenta sigue siendo solo del administrador, aunque sea de alumno. */
    public function delete(User $user): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
