<?php

namespace App\Filament\Resources\QueuedEmails\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\QueuedEmails\QueuedEmailResource;

class ListQueuedEmails extends ListRecords
{
    protected static string $resource = QueuedEmailResource::class;

    protected static ?string $title = 'Avisos por email';
}
