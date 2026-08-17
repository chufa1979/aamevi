<?php

namespace App\Support;

use App\Models\User;

/**
 * El menú del sitio, recortado a quien lo mira.
 *
 * `config/navigation.php` describe la navegación completa; acá se saca lo que
 * esa persona no puede abrir. Casi todo el menú es del aula, y el aula es de los
 * alumnos: a un administrador o a un docente le aparecían «Cursos», «Mi
 * progreso» y «Certificados», y las tres le daban 403. Un menú con puertas que
 * rebotan es peor que un menú corto.
 *
 * Un ítem sin `roles` lo ve todo el mundo.
 */
class Navigation
{
    /** @return array<int, array<string, mixed>> */
    public static function main(?User $user): array
    {
        return [
            ...self::filtrar(config('navigation.main', []), $user),
            ...self::panel($user),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function footer(?User $user): array
    {
        return self::filtrar(config('navigation.footer', []), $user);
    }

    /**
     * El acceso al panel, para quien tenga uno.
     *
     * Va en el menú principal y no sólo en la barra de cortesía porque, sacadas
     * las secciones del aula, a un docente le quedaba un menú de dos ítems sin
     * ninguno que llevara a su trabajo.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function panel(?User $user): array
    {
        return match (true) {
            $user?->isAdmin() ?? false => [
                ['label' => 'Administración', 'href' => '/admin', 'match' => 'admin*'],
            ],
            $user?->isTeacher() ?? false => [
                ['label' => 'Mis cursos', 'href' => '/profesores', 'match' => 'profesores*'],
            ],
            default => [],
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private static function filtrar(array $items, ?User $user): array
    {
        return array_values(array_filter(
            $items,
            fn (array $item): bool => self::alcanza($item, $user),
        ));
    }

    /** @param array<string, mixed> $item */
    private static function alcanza(array $item, ?User $user): bool
    {
        $roles = $item['roles'] ?? null;

        if ($roles === null) {
            return true;
        }

        return $user !== null && in_array($user->role->value, $roles, true);
    }
}
