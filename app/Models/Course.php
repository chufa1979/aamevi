<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Model;
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
            ->whereIn('status', [
                EnrollmentStatus::Approved,
                EnrollmentStatus::Active,
                EnrollmentStatus::Completed,
            ])
            ->count();
    }

    public function isFull(): bool
    {
        return $this->occupiedSeats() >= $this->max_students;
    }
}
