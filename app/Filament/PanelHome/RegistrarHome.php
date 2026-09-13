<?php

namespace App\Filament\PanelHome;

use Filament\Panel;
use Filament\Pages\Page;
use App\Filament\Resources\EnrollmentRequests\EnrollmentRequestResource;

/**
 * Mismo motivo que `TeacherHome`: reclama `/` y redirige a las solicitudes,
 * que es donde arranca este perfil. Ver el comentario de `getRoutePath()` en
 * `TeacherHome` — `$routePath` no alcanza sola, hay que sobreescribir el
 * método.
 */
class RegistrarHome extends Page
{
    protected static string $routePath = '/';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.empty';

    public static function getRoutePath(Panel $panel): string
    {
        return static::$routePath;
    }

    /**
     * Sin tipo de retorno: `redirect()` adentro de un componente Livewire
     * devuelve `Livewire\Features\SupportRedirects\Redirector`, no
     * `Illuminate\Http\RedirectResponse`.
     */
    public function mount()
    {
        return redirect(EnrollmentRequestResource::getUrl('index', panel: 'administracion'));
    }
}
