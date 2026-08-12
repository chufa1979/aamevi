<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\QuizAttemptFactory;
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

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    public function isInProgress(): bool
    {
        return ! $this->isSubmitted();
    }
}
