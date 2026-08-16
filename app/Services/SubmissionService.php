<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\CourseClass;
use App\Models\ClassContent;
use App\Models\TaskSubmission;
use App\Enums\ClassContentType;
use App\Enums\SubmissionStatus;
use Illuminate\Http\UploadedFile;
use App\Events\CourseProgressAdvanced;
use App\Exceptions\SubmissionException;
use Illuminate\Database\Eloquent\Collection;

/**
 * El ciclo de una entrega: subirla, corregirla y publicarla.
 *
 * Es el espejo de `QuizService` para lo que no se puede corregir solo. Las
 * transiciones son métodos que validan de dónde vienen, igual que en
 * `CourseEnrollment`: corregir dos veces la misma entrega o publicar una sin
 * corregir son errores de programación y rompen.
 */
class SubmissionService
{
    /** Dónde van los archivos mientras no exista Google Cloud Storage. */
    private const DISCO = 'public';

    private const CARPETA = 'submissions';

    /**
     * Guarda la entrega del alumno.
     *
     * @throws SubmissionException si no es una tarea, si ya entregó y no se la
     *                             desaprobaron, o si venció la fecha
     */
    public function submit(Student $student, ClassContent $content, UploadedFile $archivo): TaskSubmission
    {
        if (! $content->isTask()) {
            throw SubmissionException::notATask($content);
        }

        if ($content->isPastDue()) {
            throw SubmissionException::pastDue($content);
        }

        $ultima = $this->latestOf($student, $content);

        // Una entrega por tarea. La reentrega existe sólo para corregir un
        // trabajo desaprobado, no para reemplazar el que está en la fila.
        if ($ultima !== null && ! $ultima->allowsResubmission()) {
            throw SubmissionException::alreadySubmitted();
        }

        return TaskSubmission::create([
            'content_id' => $content->getKey(),
            'student_id' => $student->getKey(),
            'attempt_number' => ($ultima?->attempt_number ?? 0) + 1,
            'file_path' => $archivo->store(self::CARPETA, self::DISCO),
            // El nombre original se guarda aparte: el del disco es un hash
            'file_name' => $archivo->getClientOriginalName(),
            'submitted_at' => now(),
            'status' => SubmissionStatus::Pending,
        ]);
    }

    /**
     * Corrige la entrega.
     *
     * @throws SubmissionException si ya estaba corregida o la nota está fuera de escala
     */
    public function grade(
        TaskSubmission $submission,
        ?Teacher $teacher,
        float $nota,
        bool $aprobada,
        ?string $devolucion = null,
    ): TaskSubmission {
        if ($submission->isGraded()) {
            throw SubmissionException::alreadyGraded();
        }

        if ($nota < 1 || $nota > 10) {
            throw SubmissionException::invalidGrade($nota);
        }

        $submission->update([
            'grade' => $nota,
            'status' => $aprobada ? SubmissionStatus::Approved : SubmissionStatus::Rejected,
            'feedback' => $devolucion,
            'graded_by' => $teacher?->getKey(),
            'graded_at' => now(),
        ]);

        return $submission->fresh();
    }

    /**
     * Hace visible la nota para el alumno.
     *
     * @throws SubmissionException si todavía no fue corregida
     */
    public function publish(TaskSubmission $submission): TaskSubmission
    {
        if (! $submission->isGraded()) {
            throw SubmissionException::notGraded();
        }

        if (! $submission->isPublished()) {
            $submission->update(['published_at' => now()]);

            /*
             * Publicar puede ser lo último que faltaba para terminar el curso.
             * Se avisa por entrega y no por tanda: resolver a quién le cambió
             * algo desde `publishAll()` costaría lo mismo, y así el aviso sale
             * también cuando se publica una sola.
             */
            CourseProgressAdvanced::dispatch(
                $submission->student,
                $submission->content->class->module->course,
            );
        }

        return $submission->fresh();
    }

    /**
     * Publica todas las correcciones pendientes de un curso.
     *
     * Es lo que hace que separar corregir de publicar no sea una molestia: el
     * docente corrige a lo largo de la semana y suelta la tanda entera de una.
     *
     * @return int cuántas se publicaron
     */
    public function publishAll(Course $course): int
    {
        $pendientes = $this->submissionsOf($course)
            ->filter(fn (TaskSubmission $s): bool => $s->isGraded() && ! $s->isPublished());

        foreach ($pendientes as $submission) {
            $this->publish($submission);
        }

        return $pendientes->count();
    }

    /** La última entrega del alumno para esa tarea, o null si nunca entregó. */
    public function latestOf(Student $student, ClassContent $content): ?TaskSubmission
    {
        return TaskSubmission::where('content_id', $content->getKey())
            ->where('student_id', $student->getKey())
            ->orderByDesc('attempt_number')
            ->first();
    }

    /** ¿Puede entregar ahora? */
    public function canSubmit(Student $student, ClassContent $content): bool
    {
        if (! $content->isTask() || $content->isPastDue()) {
            return false;
        }

        $ultima = $this->latestOf($student, $content);

        return $ultima === null || $ultima->allowsResubmission();
    }

    /**
     * Tareas de la clase que el alumno todavía no entregó.
     *
     * Lo usa `ProgressService` para no dar la clase por completada mientras
     * quede algo sin entregar.
     *
     * @return Collection<int, ClassContent>
     */
    public function pendingFor(Student $student, CourseClass $class): Collection
    {
        $tareas = $class->contents()->where('type', ClassContentType::Task)->get();

        if ($tareas->isEmpty()) {
            return $tareas;
        }

        $entregadas = TaskSubmission::where('student_id', $student->getKey())
            ->whereIn('content_id', $tareas->pluck('id'))
            ->pluck('content_id')
            ->unique();

        return $tareas->reject(fn (ClassContent $t): bool => $entregadas->contains($t->getKey()));
    }

    /**
     * Tareas del curso que al alumno todavía no le aprobaron.
     *
     * Cuenta las que no entregó, las que están en corrección y las que le
     * desaprobaron. Es más exigente que `pendingFor()` a propósito: para pasar de
     * clase alcanza con haber entregado —esperar la corrección dejaría al alumno
     * detenido por otra persona—, pero el certificado dice que aprobó el curso, y
     * eso no se puede afirmar mientras haya algo sin corregir.
     *
     * También pide que la aprobación esté **publicada**: emitir el certificado
     * antes delataría una nota que el docente todavía no comunicó.
     *
     * @return Collection<int, ClassContent>
     */
    public function unapprovedIn(Student $student, Course $course): Collection
    {
        $tareas = ClassContent::query()
            ->where('type', ClassContentType::Task)
            ->whereIn('class_id', CourseClass::query()
                ->whereIn('module_id', $course->modules()->select('id'))
                ->select('id'))
            ->get();

        if ($tareas->isEmpty()) {
            return $tareas;
        }

        $aprobadas = TaskSubmission::where('student_id', $student->getKey())
            ->whereIn('content_id', $tareas->pluck('id'))
            ->where('status', SubmissionStatus::Approved)
            ->whereNotNull('published_at')
            ->pluck('content_id')
            ->unique();

        return $tareas->reject(fn (ClassContent $t): bool => $aprobadas->contains($t->getKey()));
    }

    /**
     * Todas las entregas de un curso, con lo necesario para listarlas.
     *
     * @return Collection<int, TaskSubmission>
     */
    public function submissionsOf(Course $course): Collection
    {
        return TaskSubmission::query()
            ->whereIn('content_id', ClassContent::query()
                ->whereIn('class_id', CourseClass::query()
                    ->whereIn('module_id', $course->modules()->select('id'))
                    ->select('id'))
                ->select('id'))
            ->with(['content.class.module', 'student.user', 'gradedBy.user'])
            ->orderByDesc('submitted_at')
            ->get();
    }
}
