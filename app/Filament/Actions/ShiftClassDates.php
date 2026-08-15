<?php

namespace App\Filament\Actions;

use Filament\Actions\BulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

/**
 * Corre el cronograma en bloque.
 *
 * Reprogramar clase por clase es la tarea que más rápido se vuelve insoportable
 * para un docente. Vive acá y no en una pantalla concreta porque hace falta en
 * dos: en las clases de un módulo y en el cronograma del curso entero.
 */
class ShiftClassDates
{
    public static function make(): BulkAction
    {
        return BulkAction::make('shiftDates')
            ->label('Correr fechas')
            ->icon('heroicon-o-calendar-days')
            ->schema([
                TextInput::make('days')
                    ->label('Días')
                    ->numeric()
                    ->required()
                    ->default(7)
                    ->helperText('Podés usar un número negativo para adelantarlas.'),
            ])
            ->action(function (Collection $records, array $data): void {
                $days = (int) $data['days'];

                foreach ($records as $record) {
                    $record->update([
                        'activation_date' => $record->activation_date->addDays($days),
                    ]);
                }

                Notification::make()
                    ->title(count($records).' clase(s) reprogramada(s)')
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
