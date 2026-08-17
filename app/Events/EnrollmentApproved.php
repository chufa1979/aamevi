<?php

namespace App\Events;

use App\Models\CourseEnrollment;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Le aprobaron la inscripción a un alumno.
 *
 * Va como evento y no como una llamada dentro de la acción del panel porque
 * `approve()` se llama desde más de un lado —hoy el panel y el seeder, mañana lo
 * que venga— y el aviso tiene que salir siempre, no cuando alguien se acuerde.
 */
class EnrollmentApproved
{
    use Dispatchable;

    public function __construct(public readonly CourseEnrollment $enrollment) {}
}
