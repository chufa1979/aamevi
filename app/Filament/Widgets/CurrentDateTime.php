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
    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.current-date-time';
}
