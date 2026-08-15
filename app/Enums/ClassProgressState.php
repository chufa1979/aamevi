<?php

namespace App\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * En qué situación está una clase para un alumno concreto.
 *
 * Es la lectura del gateo de §3-B desde el lado del docente: `ProgressService`
 * responde "¿puede entrar?" para una clase, y esto nombra cada resultado
 * posible para poder pintar la grilla de seguimiento.
 */
enum ClassProgressState: string implements HasColor, HasIcon, HasLabel
{
    /** Aprobada: cuenta para el avance y habilita la siguiente. */
    case Completed = 'completed';

    /** La abrió y todavía no la aprobó. */
    case InProgress = 'in_progress';

    /** Puede entrar, pero todavía no lo hizo. */
    case Available = 'available';

    /** Cerrada porque no llegó su fecha de activación. */
    case Scheduled = 'scheduled';

    /** Cerrada porque le falta aprobar la clase anterior del módulo. */
    case Locked = 'locked';

    public function getLabel(): string
    {
        return match ($this) {
            self::Completed => 'Aprobada',
            self::InProgress => 'En curso',
            self::Available => 'Sin empezar',
            self::Scheduled => 'No habilitada',
            self::Locked => 'Bloqueada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Completed => 'success',
            self::InProgress => 'warning',
            self::Available => 'gray',
            self::Scheduled => 'info',
            self::Locked => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Completed => 'heroicon-o-check-circle',
            self::InProgress => 'heroicon-o-play-circle',
            self::Available => 'heroicon-o-minus-circle',
            self::Scheduled => 'heroicon-o-clock',
            self::Locked => 'heroicon-o-lock-closed',
        };
    }

    /** ¿El alumno puede abrir la clase en este estado? */
    public function isOpen(): bool
    {
        return in_array($this, [self::Completed, self::InProgress, self::Available], true);
    }
}
