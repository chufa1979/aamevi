<?php

namespace App\Filament\Resources\QueuedEmails\Tables;

use App\Enums\EmailType;
use App\Enums\EmailStatus;
use Filament\Tables\Table;
use App\Models\QueuedEmail;
use Filament\Actions\Action;
use Illuminate\Contracts\View\View;
use App\Services\NotificationService;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;

class QueuedEmailsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('recipient.email')
                    ->label('Destinatario')
                    ->description(fn (QueuedEmail $record): ?string => $record->recipient?->full_name)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email_type')
                    ->label('Tipo')
                    ->badge(),

                TextColumn::make('subject')
                    ->label('Asunto')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    // Los reintentos sólo se muestran cuando hubo alguno: una
                    // columna con «0 intentos» en todas las filas no dice nada
                    ->description(fn (QueuedEmail $record): ?string => $record->retry_count > 0
                        ? $record->retry_count.($record->retry_count === 1 ? ' intento' : ' intentos')
                        : null),

                TextColumn::make('scheduled_at')
                    ->label('Programado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('sent_at')
                    ->label('Enviado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(EmailStatus::class),

                SelectFilter::make('email_type')
                    ->label('Tipo')
                    ->options(EmailType::class),
            ])
            ->recordActions([
                Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (QueuedEmail $record): string => $record->subject)
                    ->modalWidth('3xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(fn (QueuedEmail $record): View => view('filament.queued-email', [
                        'email' => $record,
                    ])),

                /*
                 * Reintentar es para los que se rindieron; mandar ahora, para los
                 * que están esperando su hora. Son dos cosas distintas y por eso
                 * son dos botones: uno borra el historial de fallos, el otro no.
                 */
                Action::make('reintentar')
                    ->label('Reintentar')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->visible(fn (QueuedEmail $record): bool => $record->status === EmailStatus::Failed)
                    ->action(function (QueuedEmail $record): void {
                        app(NotificationService::class)->retry($record);

                        Notification::make()
                            ->title('Vuelve a la cola')
                            ->body('Sale en la próxima corrida del worker.')
                            ->success()
                            ->send();
                    }),

                Action::make('enviar')
                    ->label('Enviar ahora')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->visible(fn (QueuedEmail $record): bool => $record->isPending())
                    ->action(function (QueuedEmail $record): void {
                        $salio = app(NotificationService::class)->send($record);

                        Notification::make()
                            ->title($salio ? 'Enviado' : 'No se pudo enviar')
                            ->body($salio ? null : $record->fresh()->last_error)
                            ->status($salio ? 'success' : 'danger')
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No hay avisos')
            ->emptyStateDescription('Acá aparecen los correos que la plataforma le manda a los alumnos.');
    }
}
