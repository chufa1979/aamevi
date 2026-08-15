<?php

namespace App\Filament\Resources\Students\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Students\StudentResource;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;
}
