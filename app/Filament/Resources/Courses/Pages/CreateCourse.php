<?php

namespace App\Filament\Resources\Courses\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Courses\CourseResource;

class CreateCourse extends CreateRecord
{
    protected static string $resource = CourseResource::class;
}
