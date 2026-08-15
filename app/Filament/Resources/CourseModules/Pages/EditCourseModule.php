<?php

namespace App\Filament\Resources\CourseModules\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\CourseModules\CourseModuleResource;

class EditCourseModule extends EditRecord
{
    protected static string $resource = CourseModuleResource::class;

    protected static ?string $navigationLabel = 'Datos y examen';

    protected static ?string $title = 'Datos del módulo';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
