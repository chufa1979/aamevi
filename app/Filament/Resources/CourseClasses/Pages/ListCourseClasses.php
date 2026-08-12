<?php

namespace App\Filament\Resources\CourseClasses\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\CourseClasses\CourseClassResource;

class ListCourseClasses extends ListRecords
{
    protected static string $resource = CourseClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
