<?php

namespace App\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * En qué quedó un aviso de la cola.
 *
 * `Failed` no es definitivo por sí solo: el worker reintenta hasta el máximo y
 * recién ahí lo deja quieto. Lo que distingue «falló y va a reintentar» de
 * «falló y se rindió» es `retry_count`, no el estado.
 */
enum EmailStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'En cola',
            self::Sent => 'Enviado',
            self::Failed => 'Falló',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Sent => 'success',
            self::Failed => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Pending => 'heroicon-o-clock',
            self::Sent => 'heroicon-o-paper-airplane',
            self::Failed => 'heroicon-o-exclamation-triangle',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
