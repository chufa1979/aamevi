<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\StudentNoteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Un asiento de la bitácora administrativa de un alumno.
 *
 * Sin `updated_at`: se escribe una vez y no se corrige. Para dejar constancia
 * de algo nuevo se agrega una fila, no se edita una existente.
 */
class StudentNote extends Model
{
    /** @use HasFactory<StudentNoteFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'author_id',
        'body',
        'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
