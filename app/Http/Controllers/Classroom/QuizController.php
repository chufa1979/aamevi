<?php

namespace App\Http\Controllers\Classroom;

use App\Models\Quiz;
use App\Models\Student;
use Illuminate\View\View;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use App\Services\QuizService;
use App\Exceptions\QuizException;
use App\Services\ProgressService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * Rendir una evaluación: la autoevaluación de una clase o el examen de un módulo.
 *
 * Las dos comparten pantalla porque para el alumno son lo mismo —preguntas de
 * opción múltiple, un envío, una nota—; lo que cambia es de dónde salen las
 * preguntas y qué las habilita, y eso lo resuelven `Quiz` y `ProgressService`.
 *
 * A diferencia del sistema que tomamos de referencia, **la respuesta correcta
 * nunca sale del servidor**: el formulario lleva sólo los textos de las
 * opciones, y la corrección la hace `QuizService::submit()` contra la base.
 */
class QuizController extends Controller
{
    public function show(Request $request, Quiz $quiz, ProgressService $progreso, QuizService $quizzes): View
    {
        $student = $request->user()->student;

        $this->autorizar($progreso, $student, $quiz);

        /*
         * Volver acá después de entregar muestra el resultado, no abre otro
         * intento: sin esto, recargar la pantalla de resultado consumiría un
         * intento por vez.
         */
        if ($recien = $this->recienEntregado($request, $quiz, $student, $quizzes)) {
            return $recien;
        }

        try {
            $attempt = $quizzes->start($quiz, $student);
        } catch (QuizException $e) {
            return view('classroom.quiz-closed', [
                'quiz' => $quiz,
                'cursoActual' => $this->curso($quiz),
                'motivo' => $e->getMessage(),
                'intentos' => $quizzes->attemptsOf($quiz, $student),
                'aprobado' => $quizzes->hasPassed($quiz, $student),
            ]);
        }

        return view('classroom.quiz', [
            'quiz' => $quiz,
            'cursoActual' => $this->curso($quiz),
            'attempt' => $attempt->load('questions.options'),
            'intentosRestantes' => $quizzes->attemptsLeft($quiz, $student),
        ]);
    }

    public function submit(Request $request, Quiz $quiz, ProgressService $progreso, QuizService $quizzes): RedirectResponse
    {
        $student = $request->user()->student;

        $this->autorizar($progreso, $student, $quiz);

        $attempt = $quizzes->attemptsOf($quiz, $student)->last(fn ($a): bool => $a->isInProgress());

        abort_if($attempt === null, 409, 'No hay un intento en curso.');

        /*
         * Se validan las claves contra las preguntas del intento: `submit()`
         * rechaza las ajenas, pero llegar con una validación limpia da un
         * mensaje mejor que una excepción de dominio.
         */
        $asignadas = $attempt->questions->pluck('id');

        $respuestas = collect($request->input('respuestas', []))
            ->only($asignadas->all())
            ->map(fn ($opcion): ?string => filled($opcion) ? (string) $opcion : null)
            ->all();

        // Las que no vinieron se mandan en null: quedan registradas como no contestadas
        foreach ($asignadas as $id) {
            $respuestas[$id] ??= null;
        }

        $attempt = $quizzes->submit($attempt, $respuestas);

        // Aprobar la autoevaluación de una clase la da por completada
        if ($attempt->passed && ! $quiz->isModuleExam() && $quiz->class !== null) {
            $progreso->complete($student, $quiz->class);
        }

        return redirect()
            ->route('classroom.quiz', $quiz)
            ->with('resultado', $attempt->getKey());
    }

    /**
     * La pantalla de resultado, si venimos de entregar.
     *
     * El intento se busca acotado al alumno y a la evaluación: el id viaja en la
     * sesión, pero no cuesta nada asegurarse de que sea suyo.
     */
    private function recienEntregado(Request $request, Quiz $quiz, Student $student, QuizService $quizzes): ?View
    {
        $id = $request->session()->get('resultado');

        if ($id === null) {
            return null;
        }

        $attempt = QuizAttempt::query()
            ->where('quiz_id', $quiz->getKey())
            ->where('student_id', $student->getKey())
            ->whereNotNull('submitted_at')
            ->with(['answers.question.options', 'answers.selectedOption'])
            ->find($id);

        if ($attempt === null) {
            return null;
        }

        return view('classroom.quiz-result', [
            'quiz' => $quiz,
            'cursoActual' => $this->curso($quiz),
            'attempt' => $attempt,
            'intentosRestantes' => $quizzes->attemptsLeft($quiz, $student),
        ]);
    }

    /** El curso al que pertenece la evaluación, sea de clase o de módulo. */
    private function curso(Quiz $quiz)
    {
        return $quiz->isModuleExam()
            ? $quiz->module?->course
            : $quiz->class?->module?->course;
    }

    /**
     * Quién puede abrirla.
     *
     * La autoevaluación sigue el gateo de su clase; el examen, el del módulo.
     * En los dos casos el motivo viaja en el 403 para que el alumno sepa qué le
     * falta.
     */
    private function autorizar(ProgressService $progreso, Student $student, Quiz $quiz): void
    {
        $motivo = $quiz->isModuleExam()
            ? $progreso->moduleExamLockReason($student, $quiz->module)
            : $progreso->lockReason($student, $quiz->class);

        abort_if($motivo !== null, 403, $motivo);
    }
}
