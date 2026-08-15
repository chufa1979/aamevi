<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\TeacherPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    TeacherPanelProvider::class,
];
