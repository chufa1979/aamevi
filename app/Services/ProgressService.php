<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Student;
use App\Models\CourseClass;
use App\Enums\EnrollmentStatus;
use App\Models\StudentProgress;

/**
 * Quién puede ver qué, y qué lleva completado.
 *
 * La progresión de §3-B tiene tres condiciones acumulativas para abrir una
 * clase: el alumno está inscripto y aprobado en el curso, llegó la fecha de
 * activación, y aprobó la clase anterior.
 */
class ProgressService
{
    public function __construct(private readonly QuizService $quizzes) {}

    /** ¿El alumno está cursando este curso? */
    public function isEnrolled(Student $student, Course $course): bool
    {
        return $course->enrollments()
            ->where('student_id', $student->getKey())
            ->whereIn('status', [
                EnrollmentStatus::Approved,
                EnrollmentStatus::Active,
                EnrollmentStatus::Completed,
            ])
            ->exists();
    }

    /**
     * ¿Puede entrar a esta clase?
     *
     * Se evalúan las tres condiciones por separado para poder explicarle al
     * alumno cuál le falta, en lugar de un "no tenés acceso" a secas.
     */
    public function canAccess(Student $student, CourseClass $class): bool
    {
        return $this->isEnrolled($student, $class->module->course)
            && $class->isAvailable()
            && $this->previousIsCompleted($student, $class);
    }

    /** Motivo por el que una clase está cerrada, o null si está abierta. */
    public function lockReason(Student $student, CourseClass $class): ?string
    {
        if (! $this->isEnrolled($student, $class->module->course)) {
            return 'No estás inscripto en este curso.';
        }

        if (! $class->isAvailable()) {
            return 'Se habilita el '.$class->activation_date->format('d/m/Y').'.';
        }

        if (! $this->previousIsCompleted($student, $class)) {
            return 'Primero tenés que aprobar la clase anterior.';
        }

        return null;
    }

    /**
     * La clase anterior del mismo módulo, o null si es la primera.
     *
     * El gateo es por módulo y no por curso entero: los módulos se habilitan por
     * fecha, y encadenar todo el curso dejaría al alumno trabado en la última
     * clase de un módulo que todavía no cursó.
     */
    public function previousClass(CourseClass $class): ?CourseClass
    {
        return CourseClass::where('module_id', $class->module_id)
            ->where('order_number', '<', $class->order_number)
            ->orderByDesc('order_number')
            ->first();
    }

    public function previousIsCompleted(Student $student, CourseClass $class): bool
    {
        $anterior = $this->previousClass($class);

        return $anterior === null || $this->hasCompleted($student, $anterior);
    }

    public function hasCompleted(Student $student, CourseClass $class): bool
    {
        return StudentProgress::where('student_id', $student->getKey())
            ->where('class_id', $class->getKey())
            ->whereNotNull('completed_at')
            ->exists();
    }

    /** Registra que el alumno abrió la clase. */
    public function start(Student $student, CourseClass $class): StudentProgress
    {
        return StudentProgress::firstOrCreate(
            ['student_id' => $student->getKey(), 'class_id' => $class->getKey()],
            ['started_at' => now()],
        );
    }

    /**
     * Da la clase por completada.
     *
     * Si tiene quiz, exige haberlo aprobado: marcarla completa sin aprobar
     * saltearía la evaluación, que es justo lo que la progresión protege.
     */
    public function complete(Student $student, CourseClass $class): bool
    {
        if ($class->quiz && ! $this->quizzes->hasPassed($class->quiz, $student)) {
            return false;
        }

        $this->start($student, $class)->update(['completed_at' => now()]);

        return true;
    }

    /** Porcentaje del curso completado por el alumno. */
    public function courseProgress(Student $student, Course $course): int
    {
        $total = $course->classes()->count();

        if ($total === 0) {
            return 0;
        }

        $completadas = StudentProgress::where('student_id', $student->getKey())
            ->whereNotNull('completed_at')
            ->whereIn('class_id', $course->classes()->select('classes.id'))
            ->count();

        return (int) round($completadas / $total * 100);
    }
}
