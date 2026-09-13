<?php

namespace App\Filament\PanelHome;

use Filament\Panel;
use Filament\Pages\Page;
use App\Filament\Resources\Courses\CourseResource;

/**
 * El panel del profesor no tenía ninguna página reclamando la ruta `/` —
 * llegar a `/profesores` a secas caía en el fallback genérico de Filament,
 * que manda al primer ítem de la navegación por orden de registro. Antes de
 * que existiera `Ayuda`, ese primer ítem resultaba ser Cursos por
 * coincidencia; en cuanto se agregó una página más, dejó de serlo.
 *
 * Esta página reclama `/` explícitamente, igual que `Filament\Pages\Dashboard`
 * en el panel de administrador, y redirige de una al lugar real donde
 * arranca el día un docente: el listado de sus cursos.
 *
 * Vive fuera de `App\Filament\Pages` a propósito: `AdminPanelProvider` hace
 * `discoverPages(in: app_path('Filament/Pages'), ...)`, y esta página
 * reclamando `/` ahí adentro chocaría con `Dashboard`, que ya lo hace en el
 * panel de administrador.
 */
class TeacherHome extends Page
{
    protected static string $routePath = '/';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.empty';

    /*
     * La propiedad `$routePath` sola no alcanza: `HasRoutes::getRoutePath()`
     * —la que de verdad arma la ruta— no la lee salvo que se la
     * sobreescriba, igual que hace `Filament\Pages\Dashboard`. Sin este
     * método, esta página se registraba en `/profesores/teacher-home` (el
     * slug por defecto) y `/profesores` seguía cayendo en el fallback
     * genérico de Filament.
     */
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
        return redirect(CourseResource::getUrl('index', panel: 'profesores'));
    }
}
