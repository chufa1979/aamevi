<?php

namespace App\Models;

use App\Exceptions\QuizException;
use Database\Factories\QuizFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Evaluación. Cuelga de una clase o de un módulo, nunca de las dos.
 *
 * - **De clase**: sortea `questions_per_student` preguntas del banco de esa clase.
 * - **De módulo**: sortea `questions_percentage` % del banco combinado de todas
 *   las clases del módulo. Con 5 clases de 5 preguntas y un 40%, son 10 de 25.
 */
class Quiz extends Model
{
    /** @use HasFactory<QuizFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'class_id',
        'module_id',
        'title',
        'questions_per_student',
        'questions_percentage',
        'passing_score',
        'max_attempts',
        'show_correct_answers',
        'randomize_options',
    ];

    protected function casts(): array
    {
        return [
            'questions_per_student' => 'integer',
            'questions_percentage' => 'integer',
            'passing_score' => 'integer',
            'max_attempts' => 'integer',
            'show_correct_answers' => 'boolean',
            'randomize_options' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Un quiz sin dueño, o con dos, no significa nada
        static::saving(function (Quiz $quiz): void {
            if (filled($quiz->class_id) === filled($quiz->module_id)) {
                throw QuizException::invalidOwner();
            }
        });
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(CourseClass::class, 'class_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    /** El curso al que pertenece, cuelgue de una clase o de un módulo. */
    public function course(): ?Course
    {
        return $this->class?->module?->course ?? $this->module?->course;
    }

    public function isModuleExam(): bool
    {
        return filled($this->module_id);
    }

    /**
     * Banco del que sortea este quiz: las preguntas activas de su clase, o las
     * de todas las clases del módulo.
     *
     * @return Builder<Question>
     */
    public function questionPool(): Builder
    {
        $pool = Question::query()->where('is_active', true);

        return $this->isModuleExam()
            ? $pool->whereIn('class_id', CourseClass::where('module_id', $this->module_id)->select('id'))
            : $pool->where('class_id', $this->class_id);
    }

    public function poolSize(): int
    {
        return $this->questionPool()->count();
    }

    /**
     * Cuántas preguntas le toca responder a un alumno.
     *
     * Nunca supera el tamaño del banco: pedir 10 de un banco de 6 devolvería 6 y
     * calificaría sobre un total distinto al configurado. Y nunca es cero si hay
     * alguna pregunta, porque un examen de cero preguntas se aprueba solo.
     */
    public function questionsToDraw(): int
    {
        $pool = $this->poolSize();

        if ($pool === 0) {
            return 0;
        }

        $cantidad = $this->isModuleExam()
            ? (int) ceil($pool * $this->questions_percentage / 100)
            : $this->questions_per_student;

        return max(1, min($cantidad, $pool));
    }

    /**
     * Sortea las preguntas de un intento.
     *
     * @return Collection<int, Question>
     */
    public function drawQuestions(): Collection
    {
        return $this->questionPool()
            ->inRandomOrder()
            ->limit($this->questionsToDraw())
            ->get();
    }

    /** ¿Se puede tomar? Sin preguntas cargadas, no. */
    public function isReady(): bool
    {
        return $this->poolSize() > 0;
    }
}
