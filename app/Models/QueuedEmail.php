<?php

namespace App\Models;

use App\Enums\EmailType;
use App\Enums\EmailStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Database\Factories\QueuedEmailFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Un aviso esperando en la cola.
 *
 * El modelo se llama `QueuedEmail` y la tabla `email_queue`: la tabla la nombró
 * §2 y una fila no es una cola, es un mensaje.
 *
 * Las transiciones —marcar enviado, marcar fallido— viven en
 * `NotificationService`, que es quien sabe cuántos reintentos quedan.
 */
class QueuedEmail extends Model
{
    /** @use HasFactory<QueuedEmailFactory> */
    use HasFactory, HasUuids;

    protected $table = 'email_queue';

    protected $fillable = [
        'recipient_id',
        'email_type',
        'subject',
        'body',
        'scheduled_at',
        'sent_at',
        'status',
        'retry_count',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'email_type' => EmailType::class,
            'status' => EmailStatus::class,
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'retry_count' => 'integer',
        ];
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function isPending(): bool
    {
        return $this->status === EmailStatus::Pending;
    }

    public function wasSent(): bool
    {
        return $this->status === EmailStatus::Sent;
    }

    /**
     * Los que le toca mandar al worker: pendientes cuya hora ya llegó.
     *
     * @param  Builder<QueuedEmail>  $query
     * @return Builder<QueuedEmail>
     */
    public function scopeDue(Builder $query): Builder
    {
        return $query
            ->where('status', EmailStatus::Pending)
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at');
    }
}
