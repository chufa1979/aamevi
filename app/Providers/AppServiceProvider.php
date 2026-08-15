<?php

namespace App\Providers;

use Filament\Support\Assets\Css;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentAsset;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /*
         * Retoques de estilo del panel. Filament sirve sus assets fuera del
         * build de Vite, así que este archivo se publica junto con los suyos
         * con `php artisan filament:assets` — ya está en deploy.sh.
         */
        FilamentAsset::register([
            Css::make('aamevi-admin', resource_path('css/filament/admin.css')),
        ]);
    }
}
