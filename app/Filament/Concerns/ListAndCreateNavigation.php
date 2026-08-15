<?php

namespace App\Filament\Concerns;

use Filament\Navigation\NavigationItem;

use function Filament\Support\original_request;

/**
 * Despliega el recurso en el menú como dos entradas, "Todos" y "Crear nuevo",
 * en lugar de una sola.
 *
 * Es la forma del panel de FID (`fid/admin/home.php`), y ahorra el paso de
 * entrar al listado para recién ahí encontrar el botón de alta, que es la
 * operación más frecuente cuando se está cargando material.
 *
 * Los ítems van sin icono a propósito: Filament rechaza que el grupo y sus
 * ítems tengan icono a la vez, y acá el icono está en el grupo.
 */
trait ListAndCreateNavigation
{
    /** @return array<NavigationItem> */
    public static function getNavigationItems(): array
    {
        $ruta = static::getRouteBaseName();

        $items = [
            NavigationItem::make('Todos')
                ->key(static::class)
                ->group(static::getNavigationGroup())
                ->badge(static::getNavigationBadge(), color: static::getNavigationBadgeColor())
                ->sort(static::getNavigationSort())
                // Cualquier pantalla del recurso salvo el alta: si no, abrir un
                // registro dejaría el menú sin nada resaltado.
                ->isActiveWhen(fn (): bool => original_request()->routeIs("{$ruta}.*")
                    && ! original_request()->routeIs("{$ruta}.create"))
                ->url(static::getUrl('index')),
        ];

        if (static::hasPage('create') && static::canCreate()) {
            $items[] = NavigationItem::make('Crear nuevo')
                ->key(static::class.'::create')
                ->group(static::getNavigationGroup())
                ->sort((static::getNavigationSort() ?? 0) + 1)
                ->isActiveWhen(fn (): bool => original_request()->routeIs("{$ruta}.create"))
                ->url(static::getUrl('create'));
        }

        return $items;
    }
}
