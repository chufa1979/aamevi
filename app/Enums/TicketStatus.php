<?php

namespace App\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * En qué quedó una consulta a mesa de ayuda.
 *
 * `Answered` no es el final: el alumno puede repreguntar y la consulta vuelve a
 * `Open`. Lo que cierra es `Closed`, y lo cierra quien la atiende — o el propio
 * alumno cuando ya no la necesita.
 */
enum TicketStatus: string implements HasColor, HasIcon, HasLabel
{
    case Open = 'open';
    case Answered = 'answered';
    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'Esperando respuesta',
            self::Answered => 'Respondida',
            self::Closed => 'Cerrada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'warning',
            self::Answered => 'success',
            self::Closed => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Open => 'heroicon-o-clock',
            self::Answered => 'heroicon-o-chat-bubble-left-right',
            self::Closed => 'heroicon-o-check-circle',
        };
    }

    public function isClosed(): bool
    {
        return $this === self::Closed;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
