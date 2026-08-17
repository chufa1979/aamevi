<?php

namespace App\Filament\Resources\QueuedEmails;

use App\Enums\EmailStatus;
use Filament\Tables\Table;
use App\Models\QueuedEmail;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\QueuedEmails\Pages\ListQueuedEmails;
use App\Filament\Resources\QueuedEmails\Tables\QueuedEmailsTable;

/**
 * La cola de avisos.
 *
 * Existe para contestar la pregunta que llega siempre: «¿le llegó el mail a
 * fulano?». Es de sólo lectura salvo por dos acciones —reintentar y mandar
 * ahora—: el contenido se armó al encolarlo y editarlo a mano dejaría en la
 * tabla algo distinto de lo que salió.
 */
class QueuedEmailResource extends Resource
{
    protected static ?string $model = QueuedEmail::class;

    protected static ?string $slug = 'avisos';

    // Sin icono propio: lo lleva el grupo, ver AdminPanelProvider
    protected static ?string $navigationLabel = 'Avisos por email';

    protected static ?string $modelLabel = 'aviso';

    protected static ?string $pluralModelLabel = 'avisos';

    protected static string|\UnitEnum|null $navigationGroup = 'Sistema';

    protected static ?int $navigationSort = 3;

    public static function getRecordTitle(?Model $record): ?string
    {
        return $record?->subject;
    }

    /** Lo que hace falta mirar: si algo se rompió, cuánto. */
    public static function getNavigationBadge(): ?string
    {
        $fallidos = QueuedEmail::where('status', EmailStatus::Failed)->count();

        return $fallidos > 0 ? (string) $fallidos : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return QueuedEmailsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQueuedEmails::route('/'),
        ];
    }
}
