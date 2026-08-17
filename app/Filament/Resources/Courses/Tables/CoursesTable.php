<?php

namespace App\Filament\Resources\Courses\Tables;

use Filament\Tables\Table;
use App\Enums\TicketStatus;
use App\Enums\EnrollmentStatus;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;

class CoursesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Curso')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('teacher.user.full_name')
                    ->label('Docente')
                    ->searchable(['users.first_name', 'users.last_name'])
                    ->placeholder('Sin asignar'),

                TextColumn::make('modules_count')
                    ->label('Módulos')
                    ->counts('modules')
                    ->alignCenter(),

                TextColumn::make('classes_count')
                    ->label('Clases')
                    ->counts('classes')
                    ->alignCenter(),

                TextColumn::make('enrollments_count')
                    ->label('Inscriptos')
                    ->counts([
                        'enrollments' => fn ($query) => $query->whereIn('status', [
                            EnrollmentStatus::Approved,
                            EnrollmentStatus::Active,
                            EnrollmentStatus::Completed,
                        ]),
                    ])
                    ->alignCenter()
                    ->description(fn ($record): string => "de {$record->max_students}"),

                /*
                 * Sólo se ve cuando hay algo esperando. Una columna con un cero
                 * en todas las filas ocupa lugar y no dice nada; en cambio un
                 * número acá es lo que le dice al docente a qué curso entrar.
                 */
                TextColumn::make('tickets_count')
                    ->label('Consultas')
                    ->counts([
                        'tickets' => fn ($query) => $query->where('status', TicketStatus::Open),
                    ])
                    // La cápsula también va condicionada: sin esto, el cero
                    // dibujaba un globo naranja vacío, que llama la atención
                    // justamente donde no hay nada que atender
                    ->badge(fn (int $state): bool => $state > 0)
                    ->color('warning')
                    ->alignCenter()
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? (string) $state : ''),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Alta')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Todavía no hay cursos');
    }
}
