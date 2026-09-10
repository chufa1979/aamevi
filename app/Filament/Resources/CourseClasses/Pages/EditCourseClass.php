<?php

namespace App\Filament\Resources\CourseClasses\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\ChainsCourseBreadcrumbs;
use App\Filament\Resources\CourseClasses\CourseClassResource;

class EditCourseClass extends EditRecord
{
    use ChainsCourseBreadcrumbs;

    protected static string $resource = CourseClassResource::class;

    protected static ?string $navigationLabel = 'Autoevaluación';

    protected static ?string $title = 'Autoevaluación de la clase';

    /** @return array<string, string> */
    public function getBreadcrumbs(): array
    {
        return [
            ...$this->moduleClassesBreadcrumbs($this->getRecord()->module),
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
