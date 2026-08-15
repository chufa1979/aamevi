<?php

namespace App\Http\Controllers\Classroom;

use App\Models\Course;
use App\Models\Student;
use Illuminate\View\View;
use App\Models\CourseClass;
use App\Models\CourseModule;
use Illuminate\Http\Request;
use App\Services\QuizService;
use App\Enums\ClassProgressState;
use App\Services\ProgressService;
use App\Http\Controllers\Controller;

/** El temario del curso y el estado de sus evaluaciones. */
class CourseController extends Controller
{
    public function show(Request $request, Course $course, ProgressService $progreso): View
    {
        $student = $request->user()->student;

        $this->autorizar($progreso, $student, $course);

        $course->load(['modules.classes.quiz', 'modules.quiz', 'teacher.user']);

        // Una sola consulta de avance para todo el curso, en vez de tres por clase
        $estados = $progreso->classStates($student, $course);

        return view('classroom.course', [
            'course' => $course,
            'cursoActual' => $course,
            'estados' => $estados,
            'avance' => $progreso->courseProgress($student, $course),
            'modulos' => $course->modules->map(fn (CourseModule $module): array => [
                'module' => $module,
                'aprobadas' => $module->classes
                    ->filter(fn (CourseClass $c): bool => ($estados[$c->getKey()] ?? null) === ClassProgressState::Completed)
                    ->count(),
                'examen' => $module->quiz,
                'examenMotivo' => $module->quiz === null
                    ? null
                    : $progreso->moduleExamLockReason($student, $module),
            ]),
        ]);
    }

    /** Todas las evaluaciones del curso con lo que el alumno hizo en cada una. */
    public function evaluations(
        Request $request,
        Course $course,
        ProgressService $progreso,
        QuizService $quizzes,
    ): View {
        $student = $request->user()->student;

        $this->autorizar($progreso, $student, $course);

        $course->load(['modules.classes.quiz', 'modules.quiz']);

        $evaluaciones = collect();

        foreach ($course->modules as $module) {
            foreach ($module->classes as $class) {
                if ($class->quiz !== null) {
                    $evaluaciones->push($this->resumen($quizzes, $student, $class->quiz, $module->title, $class->title));
                }
            }

            if ($module->quiz !== null) {
                $evaluaciones->push($this->resumen($quizzes, $student, $module->quiz, $module->title, 'Examen del módulo'));
            }
        }

        return view('classroom.evaluations', [
            'course' => $course,
            'cursoActual' => $course,
            'evaluaciones' => $evaluaciones,
        ]);
    }

    /** @return array<string, mixed> */
    private function resumen(QuizService $quizzes, Student $student, $quiz, string $modulo, string $titulo): array
    {
        $intentos = $quizzes->attemptsOf($quiz, $student);
        $mejor = $intentos->whereNotNull('score')->max('score');

        return [
            'quiz' => $quiz,
            'modulo' => $modulo,
            'titulo' => $titulo,
            'esExamen' => $quiz->isModuleExam(),
            'intentos' => $intentos->count(),
            'restantes' => $quizzes->attemptsLeft($quiz, $student),
            'aprobado' => $quizzes->hasPassed($quiz, $student),
            'mejorNota' => $mejor,
        ];
    }

    /** Un curso en el que no está inscripto no se le muestra, ni siquiera el temario. */
    private function autorizar(ProgressService $progreso, Student $student, Course $course): void
    {
        abort_unless(
            $progreso->isEnrolled($student, $course),
            403,
            'No estás inscripto en este curso.',
        );
    }
}
