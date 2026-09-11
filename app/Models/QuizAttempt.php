<?php

namespace App\Models;

use App\Services\QuizService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Database\Factories\QuizAttemptFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Intento de un alumno sobre una evaluación.
 *
 * Se crea al empezar, con las preguntas ya sorteadas y registradas, y se cierra
 * al enviar. Un intento sin `submitted_at` está en curso.
 */
class QuizAttempt extends Model
{
    /** @use HasFactory<QuizAttemptFactory> */
    use HasFactory, HasUuids;

    protected $table = 'student_quiz_attempts';

    protected $fillable = [
        'quiz_id',
        'student_id',
        'attempt_number',
        'started_at',
        'submitted_at',
        'score',
        'passed',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'attempt_number' => 'integer',
            'score' => 'integer',
            'passed' => 'boolean',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** Las preguntas que le tocaron, en el orden en que se le presentaron. */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'quiz_question_assignment', 'attempt_id', 'question_id')
            ->withPivot('assigned_order')
            ->withTimestamps()
            ->orderBy('assigned_order');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(StudentAnswer::class, 'attempt_id');
    }

    /**
     * Todos los intentos del mismo alumno en la misma evaluación.
     *
     * Es el historial que se muestra en el panel: la fila de la tabla es el
     * último, y desde ahí se llega al resto.
     *
     * @return Collection<int, QuizAttempt>
     */
    public function hermanos(): Collection
    {
        return static::query()
            ->where('quiz_id', $this->quiz_id)
            ->where('student_id', $this->student_id)
            ->with(['answers.question.options', 'answers.selectedOption'])
            ->orderBy('attempt_number')
            ->get();
    }

    /**
     * Cuántos intentos le quedan, sin volver a preguntarle a la base.
     *
     * Usa `usados`, la columna que agrega la consulta de la pantalla del panel.
     * Fuera de ahí no está cargada, así que cae en `QuizService`.
     */
    public function attemptsLeft(): int
    {
        if (! isset($this->attributes['usados'])) {
            return app(QuizService::class)->attemptsLeft($this->quiz, $this->student);
        }

        return max(0, ($this->quiz?->max_attempts ?? 0) - (int) $this->attributes['usados']);
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    public function isInProgress(): bool
    {
        return ! $this->isSubmitted();
    }

    /**
     * Desde cuándo cuentan los intentos: el último reseteo, o el principio de
     * los tiempos si nunca hubo uno.
     */
    private const DESDE_EL_RESETEO = "coalesce((
        select max(r.created_at) from quiz_attempt_resets r
        where r.quiz_id = student_quiz_attempts.quiz_id
          and r.student_id = student_quiz_attempts.student_id
    ), '1970-01-01')";

    /**
     * El último intento de cada par alumno-evaluación, con su resumen.
     *
     * `usados` y `aprobado` salen de subconsultas correlacionadas: son dos
     * columnas más en la misma consulta, no dos consultas por fila. Lo usan
     * tanto la solapa Intentos de un curso (`CourseAttempts`) como el
     * dashboard del admin, que mira todas las evaluaciones a la vez.
     *
     * @param  Builder<Quiz>  $evaluaciones
     * @return Builder<QuizAttempt>
     */
    public static function ultimosPorEvaluacion(Builder $evaluaciones): Builder
    {
        return static::query()
            ->whereIn('quiz_id', $evaluaciones)
            ->whereNotExists(fn ($q) => $q
                ->selectRaw('1')
                ->from('student_quiz_attempts as posterior')
                ->whereColumn('posterior.quiz_id', 'student_quiz_attempts.quiz_id')
                ->whereColumn('posterior.student_id', 'student_quiz_attempts.student_id')
                ->whereColumn('posterior.attempt_number', '>', 'student_quiz_attempts.attempt_number'))
            ->withCasts(['aprobado' => 'integer', 'usados' => 'integer'])
            ->addSelect([
                'student_quiz_attempts.*',

                // Los del ciclo actual: los anteriores a un reseteo no cuentan
                'usados' => static::selectRaw('count(*)')
                    ->from('student_quiz_attempts as usado')
                    ->whereColumn('usado.quiz_id', 'student_quiz_attempts.quiz_id')
                    ->whereColumn('usado.student_id', 'student_quiz_attempts.student_id')
                    ->whereRaw('usado.started_at > '.self::DESDE_EL_RESETEO),

                'aprobado' => static::selectRaw('count(*)')
                    ->from('student_quiz_attempts as aprobado')
                    ->whereColumn('aprobado.quiz_id', 'student_quiz_attempts.quiz_id')
                    ->whereColumn('aprobado.student_id', 'student_quiz_attempts.student_id')
                    ->where('aprobado.passed', true),
            ]);
    }

    /**
     * Los que se quedaron sin intentos y sin aprobar: los que están trabados.
     *
     * Va en SQL y no filtrando la colección en PHP porque lo usan un filtro de
     * tabla y un badge de navegación, y ambos se recalculan en cada pintada.
     *
     * @param  Builder<QuizAttempt>  $query
     * @return Builder<QuizAttempt>
     */
    public function scopeTrabados(Builder $query): Builder
    {
        return $query
            ->whereNotExists(fn ($q) => $q
                ->selectRaw('1')
                ->from('student_quiz_attempts as aprobado')
                ->whereColumn('aprobado.quiz_id', 'student_quiz_attempts.quiz_id')
                ->whereColumn('aprobado.student_id', 'student_quiz_attempts.student_id')
                ->where('aprobado.passed', true))
            ->whereRaw('(
                select count(*) from student_quiz_attempts usado
                where usado.quiz_id = student_quiz_attempts.quiz_id
                  and usado.student_id = student_quiz_attempts.student_id
                  and usado.started_at > '.self::DESDE_EL_RESETEO.'
            ) >= (select q.max_attempts from quizzes q where q.id = student_quiz_attempts.quiz_id)');
    }
}
