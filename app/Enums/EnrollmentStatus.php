<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Estados de una inscripción (docs/PLAN_ARQUITECTONICO.md §2 y §3-A).
 *
 * La máquina de estados es:
 *
 *   pending ──aprueba──> approved ──arranca──> active ──termina──> completed
 *      └─────rechaza───> rejected
 */
enum EnrollmentStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Active = 'active';
    case Completed = 'completed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Approved => 'Aprobada',
            self::Rejected => 'Rechazada',
            self::Active => 'En curso',
            self::Completed => 'Finalizada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'info',
            self::Rejected => 'danger',
            self::Active => 'primary',
            self::Completed => 'success',
        };
    }

    /** Estados que ocupan un lugar del cupo del curso. */
    public function ocupaCupo(): bool
    {
        return in_array($this, [self::Approved, self::Active, self::Completed], true);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
