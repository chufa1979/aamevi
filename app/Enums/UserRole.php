<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Roles de la plataforma. Corresponde al ENUM de `users.role` definido en
 * docs/PLAN_ARQUITECTONICO.md §2.
 *
 * Implementa los contratos de Filament para que el panel tome de acá las
 * etiquetas y los colores, en lugar de repetirlos en cada recurso.
 */
enum UserRole: string implements HasColor, HasLabel
{
    case Admin = 'admin';
    case Teacher = 'teacher';
    case Student = 'student';

    /** Etiqueta para mostrar en la interfaz. */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Teacher => 'Profesor',
            self::Student => 'Alumno',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::Teacher => 'primary',
            self::Student => 'gray',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
