<?php

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Ficha de alumno. Comparte la clave con `users`: no genera un id propio, lo
 * recibe del usuario al que extiende — por eso no usa el trait HasUuids.
 */
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'dni',
        'date_of_birth',
        'phone',
        'cell_phone',
        'sub_delegation',
        'delegation',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id');
    }
}
