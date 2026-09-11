<?php

namespace App\Filament\Widgets;

use App\Models\Quiz;
use Filament\Tables\Table;
use App\Models\QuizAttempt;
use Filament\Actions\Action;
use Filament\Widgets\TableWidget;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Courses\Pages\CourseAttempts;

/**
 * Los alumnos que agotaron los intentos de una evaluación sin aprobarla, en
 * cualquier curso. La solapa Intentos ya muestra esto por curso; acá está el
 * cruce de todos, que es lo que le urge ver al administrador sin entrar curso
 * por curso.
 */
class StuckStudents extends TableWidget
{
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => QuizAttempt::ultimosPorEvaluacion(Quiz::query()->select('id'))
                ->trabados()
                ->with(['quiz.class.module.course', 'quiz.module.course', 'student.user']))
            ->heading('Alumnos trabados en una evaluación')
            ->paginated([5])
            ->columns([
                TextColumn::make('student.user.full_name')
                    ->label('Alumno'),

                TextColumn::make('curso')
                    ->label('Curso')
                    ->state(fn (QuizAttempt $record): string => $record->quiz?->course()?->title ?? '—'),

                TextColumn::make('evaluacion')
                    ->label('Evaluación')
                    ->state(fn (QuizAttempt $record): string => $record->quiz?->isModuleExam()
                        ? 'Examen · '.($record->quiz->module?->title ?? '')
                        : ($record->quiz?->class?->title ?? '')),
            ])
            ->recordActions([
                Action::make('ver')
                    ->label('Ver curso')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (QuizAttempt $record): ?string => $record->quiz?->course() === null
                        ? null
                        : CourseAttempts::getUrl(['record' => $record->quiz->course()]))
                    ->visible(fn (QuizAttempt $record): bool => $record->quiz?->course() !== null),
            ])
            ->emptyStateHeading('Nadie está trabado')
            ->emptyStateDescription('Cuando un alumno agote los intentos de una evaluación sin aprobarla, va a aparecer acá.');
    }
}
