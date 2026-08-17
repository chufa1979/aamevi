<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Una comunicación del curso.
 *
 * Con `student_id` en null es para todo el curso; con un alumno, sólo para él.
 */
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'course_id',
        'student_id',
        'author_id',
        'title',
        'body',
        'published_at',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'notified_at' => 'datetime',
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

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function isForEveryone(): bool
    {
        return $this->student_id === null;
    }

    public function wasNotified(): bool
    {
        return $this->notified_at !== null;
    }

    /**
     * Las que le corresponden a este alumno: las del curso entero y las suyas.
     *
     * @param  Builder<Announcement>  $query
     * @return Builder<Announcement>
     */
    public function scopeVisibleToStudent(Builder $query, Student $student): Builder
    {
        return $query->where(fn (Builder $q): Builder => $q
            ->whereNull('student_id')
            ->orWhere('student_id', $student->getKey()));
    }
}
