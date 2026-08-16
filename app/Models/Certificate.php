<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Database\Factories\CertificateFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Certificado de finalización de un curso.
 *
 * Guarda lo que no se puede volver a calcular —el número emitido y la fecha— y
 * llega al resto por la inscripción. El PDF no se guarda: se arma al bajarlo,
 * ver `CertificateService`.
 */
class Certificate extends Model
{
    /** @use HasFactory<CertificateFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'enrollment_id',
        'certificate_number',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(CourseEnrollment::class, 'enrollment_id');
    }

    public function student(): ?Student
    {
        return $this->enrollment?->student;
    }

    public function course(): ?Course
    {
        return $this->enrollment?->course;
    }

    /**
     * Los certificados de este alumno.
     *
     * @param  Builder<Certificate>  $query
     * @return Builder<Certificate>
     */
    public function scopeOfStudent(Builder $query, Student $student): Builder
    {
        return $query->whereHas('enrollment', fn (Builder $q): Builder => $q->where('student_id', $student->getKey()));
    }
}
