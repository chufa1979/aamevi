<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\SupportMessageFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/** Un mensaje dentro de una consulta. */
class SupportMessage extends Model
{
    /** @use HasFactory<SupportMessageFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'ticket_id',
        'author_id',
        'body',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** ¿Lo escribió el alumno que abrió la consulta? */
    public function isFromStudent(): bool
    {
        return $this->author_id !== null
            && $this->author_id === $this->ticket?->student_id;
    }
}
