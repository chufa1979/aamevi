<?php

namespace App\Policies;

use App\Models\User;
use App\Models\CourseEnrollment;

/**
 * Quién puede ver, y quién puede resolver, una inscripción.
 *
 * `viewAny`/`view` van con el mismo criterio que `CoursePolicy`: admin,
 * docente y el perfil administrativo pueden mirar. Es a propósito más ancho
 * que el resto de esta policy —Filament ata la solapa "Alumnos" que ya
 * existe dentro de cada curso (`ManageCourseStudents`) a esta misma
 * habilidad, y el docente tiene que poder seguir abriéndola para saber quién
 * cursa; sólo dejó de poder resolver nada ahí.
 *
 * `create`/`update` son la parte que sí se le saca al docente: dar de alta,
 * aprobar o rechazar es trabajo administrativo. El docente nunca llega a la
 * pantalla global (`EnrollmentRequestResource`) porque su panel no la
 * registra, así que esto es defensa en profundidad; en `ManageCourseStudents`
 * es la barrera real, junto con el `visible()` de esos botones.
 */
class CourseEnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher() || $user->isRegistrar();
    }

    public function view(User $user, CourseEnrollment $enrollment): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isRegistrar();
    }

    public function update(User $user, CourseEnrollment $enrollment): bool
    {
        return $user->isAdmin() || $user->isRegistrar();
    }

    public function delete(User $user, CourseEnrollment $enrollment): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
