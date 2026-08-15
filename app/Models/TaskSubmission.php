<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Database\Factories\TaskSubmissionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Lo que un alumno entregó para una tarea.
 *
 * Las transiciones —corregir, publicar— viven en `SubmissionService` y no acá:
 * necesitan mirar las otras entregas del mismo alumno para decidir si puede
 * volver a entregar, y eso excede a una sola fila.
 */
class TaskSubmission extends Model
{
    /** @use HasFactory<TaskSubmissionFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'content_id',
        'student_id',
        'attempt_number',
        'file_path',
        'file_name',
        'submitted_at',
        'grade',
        'status',
        'feedback',
        'graded_by',
        'graded_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubmissionStatus::class,
            'attempt_number' => 'integer',
            'grade' => 'decimal:2',
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(ClassContent::class, 'content_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'graded_by');
    }

    public function isGraded(): bool
    {
        return $this->status->isGraded();
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    /**
     * ¿El alumno puede ver la nota?
     *
     * Corregida pero sin publicar es invisible para él: eso es lo que le permite
     * al docente revisar una tanda entera antes de soltarla.
     */
    public function isVisibleToStudent(): bool
    {
        return $this->isGraded() && $this->isPublished();
    }

    /**
     * ¿Puede volver a entregar después de ésta?
     *
     * Hace falta que la desaprobación esté **publicada**, no sólo puesta. Si
     * alcanzara con desaprobarla, el formulario de reentrega aparecería mientras
     * la pantalla dice «en corrección»: se contradice, y de paso delata una
     * decisión que el docente todavía no comunicó. Mientras no se publique, para
     * el alumno no pasó nada.
     */
    public function allowsResubmission(): bool
    {
        return $this->status === SubmissionStatus::Rejected && $this->isPublished();
    }

    public function url(): ?string
    {
        return blank($this->file_path) ? null : Storage::disk('public')->url($this->file_path);
    }
}
