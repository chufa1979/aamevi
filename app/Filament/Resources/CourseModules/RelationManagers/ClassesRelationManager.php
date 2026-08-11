<?php

namespace App\Filament\Resources\CourseModules\RelationManagers;

use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Toggle;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Resources\RelationManagers\RelationManager;

class ClassesRelationManager extends RelationManager
{
    protected static string $relationship = 'classes';

    protected static ?string $title = 'Clases';

    protected static ?string $modelLabel = 'clase';

    protected static ?string $pluralModelLabel = 'clases';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('title')
                    ->label('Título')
                    ->required()
                    ->maxLength(255),

                TextInput::make('order_number')
                    ->label('Orden')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->default(fn (): int => $this->getOwnerRecord()->classes()->max('order_number') + 1),

                DateTimePicker::make('activation_date')
                    ->label('Se habilita el')
                    ->seconds(false)
                    ->displayFormat('d/m/Y H:i')
                    ->required()
                    ->default(now())
                    ->helperText('Antes de esta fecha, la clase no es visible para los alumnos.'),

                Toggle::make('is_live_session')
                    ->label('Clase en vivo')
                    ->live()
                    ->helperText('Se dicta por Google Meet en la fecha indicada.'),

                TextInput::make('meet_link')
                    ->label('Enlace de Meet')
                    ->url()
                    ->maxLength(500)
                    ->visible(fn (Get $get): bool => (bool) $get('is_live_session'))
                    ->required(fn (Get $get): bool => (bool) $get('is_live_session')),

                Toggle::make('is_live_recording_available')
                    ->label('Grabación disponible')
                    ->visible(fn (Get $get): bool => (bool) $get('is_live_session')),

                Textarea::make('description')
                    ->label('Descripción')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('order_number')
            ->columns([
                TextColumn::make('order_number')
                    ->label('#')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Clase')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('activation_date')
                    ->label('Se habilita')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record): string => $record->isAvailable() ? 'success' : 'warning'),

                IconColumn::make('is_live_session')
                    ->label('En vivo')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()->label('Agregar clase'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::correrFechas(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Este módulo todavía no tiene clases');
    }

    /**
     * Correr el cronograma en bloque. Reprogramar un módulo entero clase por
     * clase es la tarea que más rápido se vuelve insoportable para un docente.
     */
    private static function correrFechas(): BulkAction
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
