<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Quiz;
use App\Models\User;
use App\Models\Student;
use App\Models\Question;
use App\Models\QuizAttempt;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use App\Models\QuizAttemptReset;
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
    public function __construct(private readonly NotificationService $avisos) {}

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

        if ($this->attemptsLeft($quiz, $student) < 1) {
            throw QuizException::noAttemptsLeft($quiz->max_attempts);
        }

        /*
         * El número de intento cuenta **todos** los que hizo, no los del ciclo
         * actual: hay un `unique(quiz_id, student_id, attempt_number)`, y
         * reiniciar la numeración después de un reseteo chocaría contra él.
         */
        $numero = $this->attemptsOf($quiz, $student)->count() + 1;

        return DB::transaction(function () use ($quiz, $student, $numero): QuizAttempt {
            $attempt = QuizAttempt::create([
                'quiz_id' => $quiz->getKey(),
                'student_id' => $student->getKey(),
                'attempt_number' => $numero,
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

            $attempt = $attempt->fresh();

            /*
             * Si éste era el último y no aprobó, el alumno queda trabado: la
             * progresión exige aprobar la autoevaluación. Se avisa acá, que es
             * donde recién se sabe el resultado — y sólo al docente, porque el
             * alumno ya lo está leyendo en pantalla.
             */
            if ($attempt->student !== null && $this->isStuck($attempt->quiz, $attempt->student)) {
                $this->avisos->attemptsExhausted($attempt->quiz, $attempt->student);
            }

            return $attempt;
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

    /**
     * Cuántos intentos le quedan.
     *
     * Se cuentan los del ciclo actual: si el docente le reseteó los intentos,
     * los anteriores quedan en el historial pero no ocupan cupo. Sin reseteos
     * —el caso normal— se comporta igual que antes.
     */
    public function attemptsLeft(Quiz $quiz, Student $student): int
    {
        $desde = $this->lastResetAt($quiz, $student);

        $usados = QuizAttempt::where('quiz_id', $quiz->getKey())
            ->where('student_id', $student->getKey())
            ->when($desde !== null, fn ($q) => $q->where('started_at', '>', $desde))
            ->count();

        return max(0, $quiz->max_attempts - $usados);
    }

    /** ¿Se quedó sin intentos y sin aprobar? Es el alumno que queda trabado. */
    public function isStuck(Quiz $quiz, Student $student): bool
    {
        return $this->attemptsLeft($quiz, $student) === 0
            && ! $this->hasPassed($quiz, $student);
    }

    /**
     * Le devuelve al alumno sus intentos.
     *
     * No borra nada: abre un ciclo nuevo. Los intentos anteriores siguen en el
     * historial —son la prueba de sobre qué se lo calificó— pero dejan de contar
     * para el límite.
     */
    public function reset(Quiz $quiz, Student $student, ?User $docente = null, ?string $motivo = null): QuizAttemptReset
    {
        $reset = QuizAttemptReset::create([
            'quiz_id' => $quiz->getKey(),
            'student_id' => $student->getKey(),
            'granted_by' => $docente?->getKey(),
            'reason' => filled($motivo) ? trim($motivo) : null,
            'created_at' => now(),
        ]);

        $this->avisos->attemptsReset($quiz, $student, $reset);

        return $reset;
    }

    /** Cuándo fue el último reseteo, o null si nunca hubo. */
    public function lastResetAt(Quiz $quiz, Student $student): ?CarbonInterface
    {
        $fecha = QuizAttemptReset::where('quiz_id', $quiz->getKey())
            ->where('student_id', $student->getKey())
            ->max('created_at');

        return $fecha === null ? null : Carbon::parse($fecha);
    }

    private function isCorrect(Question $question, string $optionId): bool
    {
        return $question->options
            ->firstWhere('id', $optionId)
            ?->is_correct === true;
    }
}
