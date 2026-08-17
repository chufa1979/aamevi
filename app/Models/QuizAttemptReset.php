<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un reseteo de intentos.
 *
 * Sin `updated_at`: se otorga una vez y no se corrige. Para dar más intentos se
 * otorga otro, y así queda el historial de cuántas veces se lo destrabó.
 */
class QuizAttemptReset extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'quiz_id',
        'student_id',
        'granted_by',
        'reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
