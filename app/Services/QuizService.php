<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\Student;
use App\Models\Question;
use App\Models\QuizAttempt;
use Illuminate\Support\Str;
use App\Exceptions\QuizException;
use Illuminate\Support\Facades\DB;

/**
 * Ciclo de vida de un intento: empezarlo, responderlo y calificarlo.
 *
 * Vive fuera de los modelos porque coordina varios —intento, asignación de
 * preguntas y respuestas— dentro de una transacción.
 */
class QuizService
{
    /**
     * Abre un intento y le sortea las preguntas.
     *
     * Si ya había uno en curso lo devuelve en lugar de abrir otro: recargar la
     * página no puede consumir un intento ni cambiar las preguntas a mitad de
     * camino.
     *
     * @throws QuizException
     */
    public function start(Quiz $quiz, Student $student): QuizAttempt
    {
        if (! $quiz->isReady()) {
            throw QuizException::notReady();
        }

        $enCurso = $this->attemptsOf($quiz, $student)
            ->firstWhere(fn (QuizAttempt $a): bool => $a->isInProgress());

        if ($enCurso) {
            return $enCurso;
        }

        $usados = $this->attemptsOf($quiz, $student)->count();

        if ($usados >= $quiz->max_attempts) {
            throw QuizException::noAttemptsLeft($quiz->max_attempts);
        }

        return DB::transaction(function () use ($quiz, $student, $usados): QuizAttempt {
            $attempt = QuizAttempt::create([
                'quiz_id' => $quiz->getKey(),
                'student_id' => $student->getKey(),
                'attempt_number' => $usados + 1,
                'started_at' => now(),
            ]);

            // El sorteo se registra ahora: es lo que permite reconstruir después
            // sobre qué preguntas se calificó a este alumno.
            $orden = 1;

            foreach ($quiz->drawQuestions() as $question) {
                $attempt->questions()->attach($question->getKey(), [
                    // attach() inserta por el query builder y saltea el modelo,
                    // así que la clave de la tabla pivote hay que darla acá
                    'id' => (string) Str::orderedUuid(),
                    'assigned_order' => $orden++,
                ]);
            }

            return $attempt->fresh();
        });
    }

    /**
     * Corrige y cierra el intento.
     *
     * @param  array<string, string|null>  $respuestas  question_id => option_id
     *
     * @throws QuizException
     */
    public function submit(QuizAttempt $attempt, array $respuestas): QuizAttempt
    {
        if ($attempt->isSubmitted()) {
            throw QuizException::alreadySubmitted();
        }

        $asignadas = $attempt->questions()->with('options')->get();

        // Responder algo que no se preguntó indica manipulación del formulario
        $ajenas = array_diff(array_keys($respuestas), $asignadas->pluck('id')->all());

        if ($ajenas !== []) {
            throw QuizException::questionNotAssigned();
        }

        return DB::transaction(function () use ($attempt, $asignadas, $respuestas): QuizAttempt {
            $correctas = 0;

            foreach ($asignadas as $question) {
                $elegida = $respuestas[$question->id] ?? null;
                $acierta = $elegida !== null && $this->isCorrect($question, $elegida);

                $correctas += $acierta ? 1 : 0;

                $attempt->answers()->create([
                    'question_id' => $question->getKey(),
                    // Las no respondidas se guardan igual, en null: dejarlas
                    // afuera haría indistinguible "no contestó" de "no se le
                    // preguntó"
                    'selected_option_id' => $elegida,
                    'is_correct' => $acierta,
                    'answered_at' => now(),
                ]);
            }

            $score = (int) round($correctas / $asignadas->count() * 100);

            $attempt->update([
                'submitted_at' => now(),
                'score' => $score,
                'passed' => $score >= $attempt->quiz->passing_score,
            ]);

            return $attempt->fresh();
        });
    }

    /** Intentos de un alumno en una evaluación, del más viejo al más nuevo. */
    public function attemptsOf(Quiz $quiz, Student $student)
    {
        return QuizAttempt::where('quiz_id', $quiz->getKey())
            ->where('student_id', $student->getKey())
            ->orderBy('attempt_number')
            ->get();
    }

    public function hasPassed(Quiz $quiz, Student $student): bool
    {
        return QuizAttempt::where('quiz_id', $quiz->getKey())
            ->where('student_id', $student->getKey())
            ->where('passed', true)
            ->exists();
    }

    /** Cuántos intentos le quedan. */
    public function attemptsLeft(Quiz $quiz, Student $student): int
    {
        return max(0, $quiz->max_attempts - $this->attemptsOf($quiz, $student)->count());
    }

    private function isCorrect(Question $question, string $optionId): bool
    {
        return $question->options
            ->firstWhere('id', $optionId)
            ?->is_correct === true;
    }
}
