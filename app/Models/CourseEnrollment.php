<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Exceptions\EnrollmentException;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\CourseEnrollmentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Inscripción de un alumno a un curso.
 *
 * Las transiciones de estado se hacen con los métodos de abajo y no asignando
 * `status` a mano: cada una valida desde qué estado se puede llegar. Un cambio
 * suelto podría marcar como finalizada una inscripción que fue rechazada.
 */
class CourseEnrollment extends Model
{
    /** @use HasFactory<CourseEnrollmentFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'course_id',
        'student_id',
        'enrollment_date',
        'status',
        'approval_date',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'enrollment_date' => 'datetime',
            'approval_date' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'approved_by');
    }

    /**
     * Aprueba la solicitud.
     *
     * @throws EnrollmentException si no está pendiente o el curso está lleno
     */
    public function approve(?Teacher $teacher = null): void
    {
        $this->ensureStatusIs(EnrollmentStatus::Pending, 'aprobar');

        if ($this->course->isFull()) {
            throw EnrollmentException::courseIsFull($this->course);
        }

        $this->update([
            'status' => EnrollmentStatus::Approved,
            'approval_date' => now(),
            'approved_by' => $teacher?->getKey(),
        ]);
    }

    /** @throws EnrollmentException si no está pendiente */
    public function reject(?Teacher $teacher = null): void
    {
        $this->ensureStatusIs(EnrollmentStatus::Pending, 'rechazar');

        $this->update([
            'status' => EnrollmentStatus::Rejected,
            'approval_date' => now(),
            'approved_by' => $teacher?->getKey(),
        ]);
    }

    /** El alumno empezó a cursar. @throws EnrollmentException */
    public function activate(): void
    {
        $this->ensureStatusIs(EnrollmentStatus::Approved, 'poner en curso');

        $this->update(['status' => EnrollmentStatus::Active]);
    }

    /** El alumno terminó el curso. @throws EnrollmentException */
    public function complete(): void
    {
        $this->ensureStatusIs(EnrollmentStatus::Active, 'finalizar');

        $this->update(['status' => EnrollmentStatus::Completed]);
    }

    public function isPending(): bool
    {
        return $this->status === EnrollmentStatus::Pending;
    }

    /** @throws EnrollmentException */
    private function ensureStatusIs(EnrollmentStatus $expected, string $accion): void
    {
        if ($this->status !== $expected) {
            throw EnrollmentException::invalidTransition($this->status, $accion);
        }
    }
}
