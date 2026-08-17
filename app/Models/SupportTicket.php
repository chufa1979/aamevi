<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\SupportTicketFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Una consulta a mesa de ayuda.
 *
 * El estado lo mueve `SupportService`, no una asignación suelta: quién escribió
 * el último mensaje es lo que decide si queda esperando respuesta o respondida,
 * y eso no se deduce mirando una sola fila.
 */
class SupportTicket extends Model
{
    /** @use HasFactory<SupportTicketFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'course_id',
        'student_id',
        'subject',
        'status',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'closed_at' => 'datetime',
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

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class, 'ticket_id')->orderBy('created_at');
    }

    public function isClosed(): bool
    {
        return $this->status->isClosed();
    }

    /** El primero es la consulta; el resto, la conversación. */
    public function question(): ?SupportMessage
    {
        return $this->messages()->first();
    }
}
