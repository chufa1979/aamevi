<?php

namespace App\Filament\Resources\Questions\Tables;

use App\Models\Course;
use App\Models\Question;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class QuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Truncadas con tooltip: el título completo de un curso o
                // módulo ocupaba tanto que apretaba Pregunta y Respuesta
                // correcta a columnas angostas, envueltas en muchas líneas.
                TextColumn::make('class.module.course.title')
                    ->label('Curso')
                    ->sortable()
                    ->toggleable()
                    ->limit(20)
                    ->tooltip(fn (Question $record): string => $record->class->module->course->title),

                TextColumn::make('class.title')
                    ->label('Clase')
                    ->description(fn (Question $record): string => $record->class->module->title)
                    ->searchable(),

                TextColumn::make('text')
                    ->label('Pregunta')
                    ->searchable()
                    // Una línea con «…» y el texto completo al pasar el mouse:
                    // envolver un enunciado largo en varias líneas por fila
                    // volvía la lista, con cientos de preguntas, imposible de
                    // recorrer de un vistazo.
                    ->formatStateUsing(fn (?string $state): string => strip_tags((string) $state))
                    ->limit(60)
                    ->tooltip(fn (Question $record): string => strip_tags($record->text)),

                TextColumn::make('options_count')
                    ->label('Opciones')
                    ->counts('options')
                    ->alignCenter(),

                TextColumn::make('correcta')
                    ->label('Respuesta correcta')
                    ->state(fn (Question $record): string => $record->correctOption()?->option_text ?? '— sin definir —')
                    ->limit(40)
                    ->tooltip(fn (Question $record): string => $record->correctOption()?->option_text ?? '— sin definir —'),

                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Filtra sobre la relación anidada: las preguntas cuelgan de la
                // clase, y la clase del módulo, y el módulo del curso.
                SelectFilter::make('curso')
                    ->label('Curso')
                    ->options(fn (): array => Course::query()
                        ->visibleTo(auth()->user())
                        ->orderBy('title')
                        ->pluck('title', 'id')
                        ->all())
                    ->query(fn ($query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($q, $courseId) => $q->whereHas(
                            'class.module',
                            fn ($m) => $m->where('course_id', $courseId),
                        ),
                    )),

                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todas')
                    ->trueLabel('Solo activas')
                    ->falseLabel('Solo inactivas'),
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
            ->emptyStateHeading('Todavía no hay preguntas cargadas');
    }
}
