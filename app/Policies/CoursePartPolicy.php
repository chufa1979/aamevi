<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Course;
use Illuminate\Database\Eloquent\Model;

/**
 * Base de los permisos sobre lo que cuelga de un curso: módulos, clases y
 * preguntas.
 *
 * Todos siguen la misma regla —el administrador puede con todo, el docente con
 * lo de sus cursos— y lo único que cambia entre ellos es cómo se llega al curso
 * desde el registro. Repetir ese `match` en tres policies idénticas invitaba a
 * que una se quedara atrás.
 *
 * `create()` no recibe registro, así que no hay curso contra el cual comparar:
 * el encuadre lo da la pantalla desde la que se crea —siempre dentro de un curso
 * ya autorizado—, no esta policy.
 */
abstract class CoursePartPolicy
{
    abstract protected function courseOf(Model $record): ?Course;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function view(User $user, Model $record): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isTeacher() && (bool) $this->courseOf($record)?->isTaughtBy($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Model $record): bool
    {
        return $this->view($user, $record);
    }

    public function delete(User $user, Model $record): bool
    {
        return $this->view($user, $record);
    }

    public function deleteAny(User $user): bool
    {
        return $this->viewAny($user);
    }
}
