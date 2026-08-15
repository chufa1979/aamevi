<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Student;
use App\Models\CourseClass;
use App\Models\CourseModule;
use App\Enums\EnrollmentStatus;
use App\Models\StudentProgress;
use App\Enums\ClassProgressState;
use Illuminate\Support\Collection;

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
            return ClassProgressState::Scheduled->describe($class);
        }

        if (! $this->previousIsCompleted($student, $class)) {
            return ClassProgressState::Locked->describe($class);
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

    /**
     * La clase siguiente del mismo módulo, o null si es la última.
     *
     * Es para el botón «continuar» del aula: sin él, terminar una clase deja al
     * alumno en una pantalla sin salida más que volver al temario.
     */
    public function nextClass(CourseClass $class): ?CourseClass
    {
        return CourseClass::where('module_id', $class->module_id)
            ->where('order_number', '>', $class->order_number)
            ->orderBy('order_number')
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

    /**
     * ¿Puede rendir el examen del módulo?
     *
     * El examen evalúa el módulo entero, así que se habilita recién cuando el
     * alumno aprobó todas sus clases. Rendirlo antes sería preguntarle sobre
     * material que todavía no vio: el banco combina las preguntas de todas las
     * clases, incluidas las que no cursó.
     */
    public function canTakeModuleExam(Student $student, CourseModule $module): bool
    {
        return $this->moduleExamLockReason($student, $module) === null;
    }

    /** Motivo por el que el examen del módulo está cerrado, o null si se puede rendir. */
    public function moduleExamLockReason(Student $student, CourseModule $module): ?string
    {
        if (! $this->isEnrolled($student, $module->course)) {
            return 'No estás inscripto en este curso.';
        }

        $quiz = $module->quiz;

        if ($quiz === null) {
            return 'Este módulo no tiene examen.';
        }

        if (! $quiz->isReady()) {
            return 'El examen todavía no tiene preguntas cargadas.';
        }

        $pendientes = $module->classes()->count() - $this->completedInModule($student, $module);

        if ($pendientes > 0) {
            return $pendientes === 1
                ? 'Te falta aprobar una clase del módulo.'
                : "Te faltan aprobar {$pendientes} clases del módulo.";
        }

        return null;
    }

    /** Cuántas clases del módulo tiene aprobadas. */
    public function completedInModule(Student $student, CourseModule $module): int
    {
        return StudentProgress::where('student_id', $student->getKey())
            ->whereNotNull('completed_at')
            ->whereIn('class_id', $module->classes()->select('classes.id'))
            ->count();
    }

    /**
     * Estado de cada clase del curso para cada alumno inscripto.
     *
     * Es lo mismo que preguntar `hasCompleted()` y `canAccess()` celda por
     * celda, pero resuelto con tres consultas en total en vez de tres por
     * celda: con 40 alumnos y 20 clases, la versión ingenua son 2400.
     *
     * Las tres condiciones se evalúan en el mismo orden que `lockReason()`, y
     * un test comprueba que la grilla y `canAccess()` no se contradigan.
     *
     * @return array<string, array<string, ClassProgressState>> indexado por alumno y clase
     */
    public function courseMatrix(Course $course): array
    {
        $alumnos = $course->enrollments()
            ->whereIn('status', [
                EnrollmentStatus::Approved,
                EnrollmentStatus::Active,
                EnrollmentStatus::Completed,
            ])
            ->pluck('student_id');

        $modules = $course->modules()->with('classes')->get();
        $clases = $modules->flatMap->classes;

        $avances = StudentProgress::query()
            ->whereIn('student_id', $alumnos)
            ->whereIn('class_id', $clases->pluck('id'))
            ->get()
            ->groupBy('student_id');

        $grilla = [];

        foreach ($alumnos as $alumno) {
            $grilla[$alumno] = $this->estados($modules, ($avances[$alumno] ?? collect())->keyBy('class_id'));
        }

        return $grilla;
    }

    /**
     * Lo mismo que `courseMatrix()` pero para un solo alumno.
     *
     * Es lo que usa el temario del aula: preguntar clase por clase con
     * `canAccess()` serían tres consultas por fila, y un curso de cuarenta
     * clases son ciento veinte para dibujar una lista.
     *
     * @return array<string, ClassProgressState> indexado por clase
     */
    public function classStates(Student $student, Course $course): array
    {
        $modules = $course->modules()->with('classes')->get();

        $propios = StudentProgress::query()
            ->where('student_id', $student->getKey())
            ->whereIn('class_id', $modules->flatMap->classes->pluck('id'))
            ->get()
            ->keyBy('class_id');

        return $this->estados($modules, $propios);
    }

    /**
     * El recorrido en sí: módulo por módulo, clase por clase, arrastrando si la
     * anterior quedó aprobada.
     *
     * No consulta nada — recibe los avances ya cargados. Tenerlo separado es lo
     * que permite que la grilla del panel y el temario del alumno apliquen
     * exactamente la misma regla sin repetirla.
     *
     * @param  Collection<int, CourseModule>  $modules
     * @param  Collection<string, StudentProgress>  $propios
     * @return array<string, ClassProgressState>
     */
    private function estados($modules, $propios): array
    {
        $estados = [];

        foreach ($modules as $module) {
            // El gateo es por módulo: cada uno arranca abierto en su primera clase
            $anteriorAprobada = true;

            foreach ($module->classes as $class) {
                $avance = $propios->get($class->getKey());

                $estado = match (true) {
                    $avance?->isCompleted() ?? false => ClassProgressState::Completed,
                    ! $class->isAvailable() => ClassProgressState::Scheduled,
                    ! $anteriorAprobada => ClassProgressState::Locked,
                    $avance !== null => ClassProgressState::InProgress,
                    default => ClassProgressState::Available,
                };

                $estados[$class->getKey()] = $estado;
                $anteriorAprobada = $estado === ClassProgressState::Completed;
            }
        }

        return $estados;
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
