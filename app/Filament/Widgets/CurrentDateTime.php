<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

/**
 * Fecha y hora actuales, al lado del saludo de bienvenida.
 *
 * La ubicación del usuario no entra acá: pedirla exige el permiso del
 * navegador en cada carga y, aun concedido, no aporta nada que la fecha ya no
 * diga — este es un panel de administración, no una app de campo.
 */
class CurrentDateTime extends Widget
{
    // Justo después de AccountWidget (-3): sin fijarlo, empata en -1 con
    // AdminStatsOverview y el orden entre ambos queda librado al desempate de
    // sortBy(), que a veces lo intercala entre el saludo y las tarjetas.
    protected static ?int $sort = -2;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.current-date-time';
}
