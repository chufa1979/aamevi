<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'title',
        'description',
        'teacher_id',
        'max_students',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'max_students' => 'integer',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class)->orderBy('order_number');
    }

    /** Todas las clases del curso, salteando el módulo intermedio. */
    public function classes(): HasManyThrough
    {
        return $this->hasManyThrough(CourseClass::class, CourseModule::class, 'course_id', 'module_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    /** Inscripciones que ocupan un lugar: aprobadas, en curso o finalizadas. */
    public function occupiedSeats(): int
    {
        return $this->enrollments()
            ->whereIn('status', EnrollmentStatus::ocupantes())
            ->count();
    }

    /**
     * Cursos que este alumno puede solicitar: activos, con lugar, y en los que
     * todavía no pidió nada.
     *
     * El cupo se compara en SQL y no filtrando después en PHP para que el
     * catálogo siga sirviendo cuando haya cientos de cursos. La subconsulta
     * cuenta los estados que ocupan lugar, los mismos que `occupiedSeats()`.
     *
     * Las rechazadas también excluyen: volver a ofrecer un curso que le
     * rechazaron invita a insistir sin que nada haya cambiado. Si más adelante
     * se quiere permitir reintentar, se saca `Rejected` de la exclusión.
     *
     * @param  Builder<Course>  $query
     * @return Builder<Course>
     */
    public function scopeAvailableFor(Builder $query, Student $student): Builder
    {
        $ocupantes = collect(EnrollmentStatus::ocupantes())->map(fn ($e): string => $e->value)->all();
        $marcadores = implode(',', array_fill(0, count($ocupantes), '?'));

        return $query
            ->where('is_active', true)
            ->whereDoesntHave('enrollments', fn (Builder $q): Builder => $q->where('student_id', $student->getKey()))
            ->whereRaw(
                "(select count(*) from course_enrollments
                    where course_enrollments.course_id = courses.id
                      and course_enrollments.status in ({$marcadores})) < courses.max_students",
                $ocupantes,
            );
    }

    public function isFull(): bool
    {
        return $this->occupiedSeats() >= $this->max_students;
    }
}
