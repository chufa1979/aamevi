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

    /**
     * El perfil administrativo: crea y acepta inscripciones, y da de alta
     * alumnos, pero no toca contenido de curso ni cuentas de docente o
     * administrador. Ver `App\Providers\Filament\RegistrarPanelProvider`.
     */
    case Registrar = 'registrar';

    /** Etiqueta para mostrar en la interfaz. */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Teacher => 'Profesor',
            self::Student => 'Alumno',
            self::Registrar => 'Administrativo',
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
            self::Registrar => 'warning',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
