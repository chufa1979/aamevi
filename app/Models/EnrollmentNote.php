<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\EnrollmentNoteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Un asiento de la bitácora administrativa de una solicitud de inscripción.
 *
 * Sin `updated_at`: se escribe una vez y no se corrige. Para dejar constancia
 * de algo nuevo se agrega una fila, no se edita una existente.
 */
class EnrollmentNote extends Model
{
    /** @use HasFactory<EnrollmentNoteFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'enrollment_id',
        'author_id',
        'body',
        'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(CourseEnrollment::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
