<?php

namespace App\Filament\Resources\Students\Tables;

use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;

class StudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Sin el withCount, la columna de inscripciones haría una consulta
            // por fila.
            ->modifyQueryUsing(fn ($query) => $query->with('student')->withCount('enrollments'))
            ->columns([
                TextColumn::make('full_name')
                    ->label('Nombre')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['last_name']),

                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('student.dni')
                    ->label('DNI')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('student.delegation')
                    ->label('Delegación')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('enrollments_count')
                    ->label('Cursos')
                    ->alignCenter()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),

                TextColumn::make('student.phone')
                    ->label('Teléfono')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Alta')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('last_name')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos'),

                TernaryFilter::make('sin_curso')
                    ->label('Inscripciones')
                    ->placeholder('Todos')
                    ->trueLabel('Sin ningún curso')
                    ->falseLabel('Con al menos uno')
                    ->queries(
                        true: fn ($query) => $query->doesntHave('enrollments'),
                        false: fn ($query) => $query->has('enrollments'),
                    ),
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
            ->emptyStateHeading('Todavía no hay alumnos')
            ->emptyStateDescription('Se dan de alta desde «Crear nuevo», que crea la cuenta y la ficha juntas.');
    }
}
