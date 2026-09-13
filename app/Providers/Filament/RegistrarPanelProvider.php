<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use App\Filament\Pages\Ayuda;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Navigation\NavigationGroup;
use App\Filament\PanelHome\RegistrarHome;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use App\Filament\Resources\Students\StudentResource;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use App\Filament\Resources\EnrollmentRequests\EnrollmentRequestResource;

/**
 * El panel del perfil administrativo.
 *
 * Igual que `/profesores`: mismo software que `/admin`, resources reusados
 * tal cual, y lo que cambia es quién mira. `UserPolicy` y
 * `CourseEnrollmentPolicy` deciden qué puede hacer —no este panel—, así que
 * la separación aguanta aunque alguien escriba la URL a mano.
 *
 * Lo que queda afuera: contenido de curso, exámenes, calificaciones,
 * seguimiento, comunicaciones, consultas, la cola de email y las cuentas de
 * docente o administrador. Ese es el trabajo del docente y del
 * administrador, no el de este perfil — ver `UserRole::Registrar`.
 *
 * No expone formulario de entrada, igual que los otros dos: se entra por
 * /login.
 */
class RegistrarPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('administracion')
            ->path('administracion')
            ->brandName('AAMEVi')
            ->brandLogo(asset('images/aamevi.svg'))
            ->darkModeBrandLogo(asset('images/aamevi-dark.svg'))
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('favicon.png'))
            ->colors([
                'primary' => Color::hex('#00b8b3'),
            ])
            ->navigationGroups([
                NavigationGroup::make('Alumnos')->icon(Heroicon::OutlinedAcademicCap),
            ])
            ->pages([
                RegistrarHome::class,
                Ayuda::class,
            ])
            /*
             * Declarados uno por uno, mismo criterio que TeacherPanelProvider:
             * la lista corta es la que dice qué ve este perfil.
             */
            ->resources([
                EnrollmentRequestResource::class,
                StudentResource::class,
            ])
            ->homeUrl(fn (): string => EnrollmentRequestResource::getUrl('index', panel: 'administracion'))
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
