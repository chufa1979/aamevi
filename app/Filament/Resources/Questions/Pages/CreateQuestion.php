<?php

namespace App\Filament\Resources\Questions\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Questions\QuestionResource;

class CreateQuestion extends CreateRecord
{
    protected static string $resource = QuestionResource::class;
}
