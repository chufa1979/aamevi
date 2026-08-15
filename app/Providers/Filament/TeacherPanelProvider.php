<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Navigation\NavigationGroup;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use App\Filament\Resources\Courses\CourseResource;
use Illuminate\Routing\Middleware\SubstituteBindings;
use App\Filament\Resources\Questions\QuestionResource;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use App\Filament\Resources\CourseClasses\CourseClassResource;
use App\Filament\Resources\CourseModules\CourseModuleResource;

/**
 * El panel del docente.
 *
 * Es el mismo software que /admin con menos cosas a la vista: reusa los recursos
 * tal cual en lugar de duplicarlos, porque un curso se administra igual lo dicte
 * quien lo dicte, y dos copias del árbol curso → módulo → clase se habrían
 * separado en cuanto se tocara una.
 *
 * Lo que cambia no es el panel sino quién mira: `Course::scopeVisibleTo()`
 * recorta las consultas y las policies deciden qué se puede hacer. Por eso la
 * separación aguanta aunque alguien escriba la URL a mano — el panel no es la
 * cerradura, es la puerta.
 *
 * Lo que queda afuera: las cuentas de usuario, que son del administrador, y el
 * alta y la baja de cursos. Los alumnos se ven desde cada curso, que es donde
 * significan algo.
 *
 * No expone formulario de entrada, igual que /admin: se entra por /login.
 */
class TeacherPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('profesores')
            ->path('profesores')
            ->brandName('AAMEVi')
            ->brandLogo(asset('images/aamevi.svg'))
            ->darkModeBrandLogo(asset('images/aamevi-dark.svg'))
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('favicon.png'))
            ->colors([
                'primary' => Color::hex('#00b8b3'),
            ])
            ->navigationGroups([
                NavigationGroup::make('Cursos')->icon(Heroicon::OutlinedBookOpen),
                NavigationGroup::make('Evaluación')->icon(Heroicon::OutlinedClipboardDocumentCheck),
            ])
            /*
             * Declarados uno por uno y no por descubrimiento: si mañana se suma
             * un recurso de administración, aparecería solo en este panel. La
             * lista corta es la que dice qué ve un docente.
             *
             * Módulos y clases no figuran en el menú —se llega desde el curso—
             * pero tienen que estar registrados para que sus rutas existan.
             */
            ->resources([
                CourseResource::class,
                CourseModuleResource::class,
                CourseClassResource::class,
                QuestionResource::class,
            ])
            // Sin panel de inicio: la primera pantalla es el listado de cursos,
            // que es con lo que un docente arranca el día
            ->homeUrl(fn (): string => CourseResource::getUrl('index', panel: 'profesores'))
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
