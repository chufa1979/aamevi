<?php

namespace App\Filament\Resources\CourseClasses\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\CourseClasses\CourseClassResource;

class EditCourseClass extends EditRecord
{
    protected static string $resource = CourseClassResource::class;

    protected static ?string $navigationLabel = 'Autoevaluación';

    protected static ?string $title = 'Autoevaluación de la clase';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
