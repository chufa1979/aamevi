<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Course;

/**
 * Quién puede hacer qué con un curso.
 *
 * Los dos paneles comparten los recursos, así que la restricción no puede vivir
 * en el panel: si estuviera ahí, entrar por la otra puerta la saltearía. Acá vale
 * siempre, y `Course::scopeVisibleTo()` la repite en las consultas para que un
 * docente ni siquiera vea el curso ajeno en un listado.
 *
 * El alta y la baja de cursos son del administrador: el docente recibe el curso
 * asignado y trabaja adentro. Si pudiera crearlos, tendría que elegir docente, y
 * si pudiera borrarlos se llevaría puestas las inscripciones de los alumnos.
 */
class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function view(User $user, Course $course): bool
    {
        return $user->isAdmin() || ($user->isTeacher() && $course->isTaughtBy($user));
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Course $course): bool
    {
        return $this->view($user, $course);
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
