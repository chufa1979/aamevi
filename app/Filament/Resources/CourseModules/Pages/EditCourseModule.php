<?php

namespace App\Filament\Resources\CourseModules\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\ChainsCourseBreadcrumbs;
use App\Filament\Resources\CourseModules\CourseModuleResource;

class EditCourseModule extends EditRecord
{
    use ChainsCourseBreadcrumbs;

    protected static string $resource = CourseModuleResource::class;

    protected static ?string $navigationLabel = 'Datos y examen';

    protected static ?string $title = 'Datos del módulo';

    /** @return array<string, string> */
    public function getBreadcrumbs(): array
    {
        return [
            ...$this->courseBreadcrumbs($this->getRecord()->course),
            $this->getRecord()->title,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
