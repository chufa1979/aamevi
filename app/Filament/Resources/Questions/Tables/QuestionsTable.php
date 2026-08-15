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
                TextColumn::make('class.module.course.title')
                    ->label('Curso')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('class.title')
                    ->label('Clase')
                    ->description(fn (Question $record): string => $record->class->module->title)
                    ->searchable(),

                TextColumn::make('text')
                    ->label('Pregunta')
                    ->searchable()
                    ->wrap()
                    // En el listado interesa el texto, no el marcado
                    ->formatStateUsing(fn (?string $state): string => strip_tags((string) $state))
                    ->limit(100),

                TextColumn::make('options_count')
                    ->label('Opciones')
                    ->counts('options')
                    ->alignCenter(),

                TextColumn::make('correcta')
                    ->label('Respuesta correcta')
                    ->state(fn (Question $record): string => $record->correctOption()?->option_text ?? '— sin definir —')
                    ->wrap()
                    ->limit(50),

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
