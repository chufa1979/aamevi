<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\TeacherPanelProvider;
use App\Providers\Filament\RegistrarPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    TeacherPanelProvider::class,
    RegistrarPanelProvider::class,
];
