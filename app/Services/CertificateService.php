<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Student;
use App\Models\Certificate;
use Illuminate\Support\Str;
use App\Enums\EnrollmentStatus;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\CourseEnrollment;
use Barryvdh\DomPDF\PDF as PdfDocument;
use App\Exceptions\CertificateException;
use Illuminate\Database\Eloquent\Collection;

/**
 * Emisión de certificados de finalización.
 *
 * Dos caminos, a propósito. El automático —`issueIfEarned()`— se dispara solo
 * cuando el alumno termina de cumplir las condiciones, y **devuelve null** si
 * todavía no las cumple: que falte una clase no es un error, es lo normal. El
 * manual —`issue()`— lo usa el docente desde el panel para los casos que la
 * regla no contempla, y ése sí lanza si algo no cierra.
 *
 * El PDF no se guarda. El certificado son sus cuatro columnas; el archivo es una
 * forma de mostrarlas y se arma al bajarlo. Guardarlo obligaría a regenerarlo
 * cada vez que se corrija un apellido o cambie la plantilla, y a limpiar los
 * viejos. Lo que no se puede recalcular —el número y la fecha— sí queda escrito.
 */
class CertificateService
{
    public function __construct(
        private readonly ProgressService $progress,
        private readonly SubmissionService $submissions,
        private readonly NotificationService $avisos,
    ) {}

    /**
     * ¿Terminó el curso?
     *
     * Dos condiciones: todas las clases completadas y ninguna tarea sin aprobar.
     * La primera ya arrastra las evaluaciones, porque una clase no se completa
     * sin haber aprobado la suya.
     */
    public function earnedBy(Student $student, Course $course): bool
    {
        return $this->blocker($student, $course) === null;
    }

    /**
     * Por qué todavía no le corresponde, o null si le corresponde.
     *
     * Es para la pantalla del alumno: «todavía no» a secas no le dice si le
     * falta cursar o le falta que le corrijan.
     */
    public function blocker(Student $student, Course $course): ?string
    {
        if (! $this->progress->isEnrolled($student, $course)) {
            return 'No estás inscripto en este curso.';
        }

        $total = $course->classes()->count();

        if ($total === 0) {
            return 'El curso todavía no tiene clases cargadas.';
        }

        $avance = $this->progress->courseProgress($student, $course);

        if ($avance < 100) {
            return "Te falta completar el {$this->faltante($avance)}% de las clases.";
        }

        $tareas = $this->submissions->unapprovedIn($student, $course);

        if ($tareas->isNotEmpty()) {
            return $tareas->count() === 1
                ? 'Falta que te aprueben «'.$tareas->first()->title.'».'
                : 'Faltan que te aprueben '.$tareas->count().' tareas del curso.';
        }

        return null;
    }

    /**
     * Emite el certificado si el alumno terminó el curso.
     *
     * Idempotente: si ya lo tiene, devuelve el que ya existe. Se llama después de
     * completar una clase y después de publicar una corrección, que son los dos
     * únicos momentos en que un curso puede pasar a estar terminado.
     */
    public function issueIfEarned(Student $student, Course $course): ?Certificate
    {
        $enrollment = $this->enrollmentOf($student, $course);

        if ($enrollment === null) {
            return null;
        }

        if ($existente = $this->of($student, $course)) {
            return $existente;
        }

        if (! $this->earnedBy($student, $course)) {
            return null;
        }

        return $this->issue($enrollment);
    }

    /**
     * Emite el certificado sin mirar si terminó.
     *
     * Es la aprobación manual de §3-E: el docente puede darlo por aprobado
     * aunque le falte algo —una tarea entregada fuera de término, un alumno que
     * rindió aparte—. La regla automática no puede contemplar esos casos, pero
     * negarlos del todo obligaría a tocar la base a mano.
     *
     * @throws CertificateException si ya tiene certificado de este curso
     */
    public function issue(CourseEnrollment $enrollment): Certificate
    {
        if ($enrollment->certificate()->exists()) {
            throw CertificateException::alreadyIssued($enrollment);
        }

        $this->cerrarInscripcion($enrollment);

        $certificate = Certificate::create([
            'enrollment_id' => $enrollment->getKey(),
            'certificate_number' => $this->numero(),
            'issued_at' => now(),
        ]);

        // Directo y no por evento: acá no hay círculo que cortar, y el aviso es
        // parte de emitir — un certificado que nadie sabe que existe no sirve
        $this->avisos->certificateIssued($certificate);

        return $certificate;
    }

    public function of(Student $student, Course $course): ?Certificate
    {
        return Certificate::query()
            ->ofStudent($student)
            ->whereHas('enrollment', fn ($q) => $q->where('course_id', $course->getKey()))
            ->first();
    }

    /** @return Collection<int, Certificate> */
    public function forStudent(Student $student): Collection
    {
        return Certificate::query()
            ->ofStudent($student)
            ->with('enrollment.course.teacher.user')
            ->orderByDesc('issued_at')
            ->get();
    }

    /** El PDF, armado en el momento a partir de lo que hay en la base. */
    public function pdf(Certificate $certificate): PdfDocument
    {
        $certificate->loadMissing('enrollment.course.teacher.user', 'enrollment.student.user');

        return Pdf::loadView('certificates.pdf', [
            'certificate' => $certificate,
            'clases' => $certificate->enrollment->course->classes()->count(),
        ])->setPaper('a4', 'landscape');
    }

    /** Nombre del archivo que baja el alumno. */
    public function fileName(Certificate $certificate): string
    {
        return Str::slug('certificado-'.$certificate->certificate_number).'.pdf';
    }

    /**
     * Deja la inscripción como finalizada.
     *
     * Es el único lugar donde una inscripción llega a `completed`. Pasa por
     * `activate()` cuando hace falta porque la máquina de estados no permite
     * saltear: aprobada → en curso → finalizada. Alguien que terminó el curso
     * estuvo cursándolo, aunque nadie lo haya registrado en su momento.
     */
    private function cerrarInscripcion(CourseEnrollment $enrollment): void
    {
        if ($enrollment->status === EnrollmentStatus::Approved) {
            $enrollment->activate();
        }

        if ($enrollment->status === EnrollmentStatus::Active) {
            $enrollment->complete();
        }
    }

    private function enrollmentOf(Student $student, Course $course): ?CourseEnrollment
    {
        return $course->enrollments()
            ->where('student_id', $student->getKey())
            ->whereIn('status', EnrollmentStatus::ocupantes())
            ->first();
    }

    /**
     * Número visible del certificado: `AAMEVI-2026-4F2A9C`.
     *
     * Lleva el año porque es lo primero que se busca al verificar uno viejo, y
     * seis caracteres al azar en lugar de un correlativo: un correlativo dice
     * cuántos certificados emitió la institución, y además obliga a bloquear la
     * tabla para no repetirlo.
     */
    private function numero(): string
    {
        do {
            $numero = sprintf('AAMEVI-%s-%s', now()->year, strtoupper(Str::random(6)));
        } while (Certificate::where('certificate_number', $numero)->exists());

        return $numero;
    }

    private function faltante(int $avance): int
    {
        return max(1, 100 - $avance);
    }
}
