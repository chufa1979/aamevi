<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Recorta el recurso a lo que el usuario tiene derecho a ver.
 *
 * Filament resuelve los registros por esta misma consulta, así que el recorte no
 * es sólo cosmético: un docente que escriba a mano la URL del curso de otro
 * recibe un 404, no la pantalla. Las policies dicen lo mismo desde el otro lado
 * —quién puede editar o borrar—, pero una policy no achica un listado.
 *
 * El modelo pone la regla en `scopeVisibleTo()`; acá sólo se aplica.
 */
trait ScopedToOwnCourses
{
    /** @return Builder<covariant \Illuminate\Database\Eloquent\Model> */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Sin sesión no hay a quién recortarle nada; el panel ya exige una
        return $user === null ? $query : $query->visibleTo($user);
    }
}
