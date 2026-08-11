<?php

namespace App\Enums;

/**
 * Roles de la plataforma. Corresponde al ENUM de `users.role` definido en
 * docs/PLAN_ARQUITECTONICO.md §2.
 */
enum UserRole: string
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

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
