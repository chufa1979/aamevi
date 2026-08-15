<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Student;
use App\Enums\EnrollmentStatus;
use App\Models\CourseEnrollment;
use App\Exceptions\EnrollmentException;

/**
 * El lado del alumno de la inscripción: pedirla.
 *
 * `CourseEnrollment` ya resuelve qué pasa después —aprobar, rechazar, activar,
 * finalizar—, pero todos esos métodos operan sobre una fila que ya existe y los
 * usa un docente. Crear la solicitud no estaba en ningún lado: el panel la
 * insertaba a mano y el seeder también, cada uno con su propia idea de qué
 * validar.
 */
class EnrollmentService
{
    /**
     * Deja una solicitud pendiente de aprobación.
     *
     * @throws EnrollmentException si el curso está cerrado, lleno, o el alumno
     *                             ya pidió antes
     */
    public function request(Student $student, Course $course): CourseEnrollment
    {
        if (! $course->is_active) {
            throw EnrollmentException::courseIsClosed($course);
        }

        if ($this->hasRequested($student, $course)) {
            throw EnrollmentException::alreadyEnrolled($course);
        }

        if ($course->isFull()) {
            throw EnrollmentException::courseIsFull($course);
        }

        return CourseEnrollment::create([
            'course_id' => $course->getKey(),
            'student_id' => $student->getKey(),
            'enrollment_date' => now(),
            // El default de la columna no llega al modelo recién creado, y sin
            // esto `isPending()` daría falso sobre la fila que acabamos de crear
            'status' => EnrollmentStatus::Pending,
        ]);
    }

    /** ¿Ya pidió este curso, en cualquier estado? */
    public function hasRequested(Student $student, Course $course): bool
    {
        return $course->enrollments()
            ->where('student_id', $student->getKey())
            ->exists();
    }

    /** La solicitud del alumno en este curso, si existe. */
    public function enrollmentOf(Student $student, Course $course): ?CourseEnrollment
    {
        return $course->enrollments()
            ->where('student_id', $student->getKey())
            ->first();
    }
}
