<?php

namespace App\Filament\Resources\CourseModules\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\CourseModules\CourseModuleResource;

class ListCourseModules extends ListRecords
{
    protected static string $resource = CourseModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
