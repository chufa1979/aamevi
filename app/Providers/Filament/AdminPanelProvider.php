<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Pages\Dashboard;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Support\Icons\Heroicon;
use Filament\Navigation\NavigationGroup;
use Filament\Widgets\FilamentInfoWidget;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            // brandName queda como texto alternativo del logo
            ->brandName('AAMEVi')
            ->brandLogo(asset('images/aamevi.svg'))
            // El isotipo lleva el texto en #333333, ilegible sobre el modo
            // oscuro; la variante lo tiene en blanco y conserva los seis colores
            ->darkModeBrandLogo(asset('images/aamevi-dark.svg'))
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('favicon.png'))
            /*
             * Sin ->login(): el panel no expone su propio formulario. Los
             * administradores entran por /login como todo el mundo, que ya
             * tiene limitador de intentos y control de cuentas desactivadas.
             * Un invitado que abra /admin cae ahí.
             */
            ->colors([
                'primary' => Color::hex('#00b8b3'),
            ])
            /*
             * El orden de los grupos se fija acá y no en cada recurso: repartido
             * en constantes `$navigationSort` sueltas, agregar un recurso obliga
             * a renumerar los demás para meterlo en el medio.
             *
             * La secuencia sigue el trabajo del docente: primero el material,
             * después quiénes lo cursan, después la evaluación, y al final la
             * administración de cuentas.
             */
            ->navigationGroups([
                NavigationGroup::make('Cursos')->icon(Heroicon::OutlinedBookOpen),
                NavigationGroup::make('Alumnos')->icon(Heroicon::OutlinedAcademicCap),
                NavigationGroup::make('Evaluación')->icon(Heroicon::OutlinedClipboardDocumentCheck),
                NavigationGroup::make('Sistema')->icon(Heroicon::OutlinedCog6Tooth),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
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
