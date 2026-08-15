<?php

namespace App\Http\Controllers\Classroom;

use Illuminate\View\View;
use App\Models\CourseClass;
use App\Models\ClassContent;
use Illuminate\Http\Request;
use App\Services\QuizService;
use App\Services\ProgressService;
use App\Services\SubmissionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/** El aula: el contenido de una clase. */
class ClassroomController extends Controller
{
    public function show(
        Request $request,
        CourseClass $class,
        ProgressService $progreso,
        QuizService $quizzes,
        SubmissionService $entregas,
    ): View {
        $student = $request->user()->student;

        /*
         * El gateo no es cosmético: escribir la URL de una clase bloqueada tiene
         * que dar 403, no mostrarla. El motivo va en el mensaje para que el
         * alumno entienda qué le falta en lugar de leer «prohibido».
         */
        $motivo = $progreso->lockReason($student, $class);

        abort_if($motivo !== null, 403, $motivo);

        $class->load(['contents', 'quiz', 'module.course']);

        // Abrir la clase cuenta como empezarla
        $progreso->start($student, $class);

        $quiz = $class->quiz;

        /*
         * El estado de cada tarea se resuelve acá y no en la vista: son dos
         * consultas por tarea y una plantilla no es lugar para eso.
         */
        $tareas = $class->contents
            ->filter(fn (ClassContent $c): bool => $c->isTask())
            ->mapWithKeys(fn (ClassContent $c): array => [$c->getKey() => [
                'entrega' => $entregas->latestOf($student, $c),
                'puedeEntregar' => $entregas->canSubmit($student, $c),
            ]]);

        return view('classroom.class', [
            'class' => $class,
            'course' => $class->module->course,
            'cursoActual' => $class->module->course,
            'completada' => $progreso->hasCompleted($student, $class),
            'quiz' => $quiz,
            'aprobado' => $quiz !== null && $quizzes->hasPassed($quiz, $student),
            'intentosRestantes' => $quiz !== null ? $quizzes->attemptsLeft($quiz, $student) : 0,
            'siguiente' => $progreso->nextClass($class),
            'tareas' => $tareas,
            // Qué le falta para cerrar la clase, si le falta algo
            'pendiente' => $progreso->completionBlocker($student, $class),
        ]);
    }

    /**
     * Marca la clase como vista.
     *
     * Sólo tiene sentido en las clases sin evaluación: donde hay una, la clase
     * se aprueba rindiéndola, y `complete()` lo rechaza igual.
     */
    public function complete(Request $request, CourseClass $class, ProgressService $progreso): RedirectResponse
    {
        $student = $request->user()->student;

        abort_if($progreso->lockReason($student, $class) !== null, 403);

        if (! $progreso->complete($student, $class)) {
            return back()->with('error', 'Para dar la clase por vista tenés que aprobar su autoevaluación.');
        }

        $siguiente = $progreso->nextClass($class);

        return $siguiente !== null
            ? redirect()->route('classroom.class', $siguiente)->with('exito', 'Clase completada.')
            : redirect()->route('classroom.course', $class->module->course)->with('exito', 'Clase completada.');
    }
}
