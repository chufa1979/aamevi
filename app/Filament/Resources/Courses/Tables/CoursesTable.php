<?php

namespace App\Filament\Resources\Courses\Tables;

use Filament\Tables\Table;
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

                TextColumn::make('max_students')
                    ->label('Cupo')
                    ->alignCenter(),

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
