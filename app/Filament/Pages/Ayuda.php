<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Los mismos videos instructivos de `/ayuda` (ver `HelpController`), pero
 * dentro del panel: un admin, profesor o administrativo vive ahí adentro, no
 * en el sitio público, y una vista de Filament fuera de él —o un link que se
 * la lleve— se siente como haberse ido de la aplicación. El alumno, en
 * cambio, ve la versión pública: no tiene panel del que salir.
 *
 * Reutiliza `config('help_videos')`, la misma fuente que usa `HelpController`
 * para la versión del alumno.
 */
class Ayuda extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?string $navigationLabel = 'Ayuda';

    protected static ?string $title = 'Ayuda';

    /*
     * Sin esto, un ítem de navegación sin `sort` explícito vale -1
     * (`NavigationItem::getSort()`), y el desempate lo gana el orden de
     * registro — acá, primero que cualquier recurso. `Panel::getRedirectUrl()`
     * manda a la ruta bare del panel (`/profesores`, `/administracion`) al
     * primer ítem de navegación, así que sin este número Ayuda se robaba el
     * home del panel entero. Pasó de verdad: ver `TeacherPanelTest`.
     */
    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.pages.ayuda';

    /** @return array<int, array{slug: string, title: string, description: string, file: string}> */
    public function getVideos(): array
    {
        return config('help_videos.'.auth()->user()->role->value, []);
    }
}
