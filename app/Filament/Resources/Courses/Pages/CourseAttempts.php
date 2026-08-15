<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Models\Quiz;
use App\Models\Course;
use Filament\Tables\Table;
use App\Models\CourseClass;
use App\Models\QuizAttempt;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\View\View;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Filament\Resources\Courses\CourseResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

/**
 * Qué rindió cada alumno y qué respondió.
 *
 * Los datos estaban guardados desde el principio —`quiz_question_assignment`
 * registra qué preguntas le tocaron a cada uno, y `student_answers` qué eligió—
 * pero no había forma de mirarlos. Sin esta pantalla, un docente al que le
 * reclaman una nota tiene que abrir la base de datos.
 *
 * Es de sólo lectura a propósito: la corrección de las evaluaciones es
 * automática, y dejar editarla acá abriría la puerta a cambiar una nota sin que
 * quede rastro de por qué.
 */
class CourseAttempts extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = CourseResource::class;

    protected static ?string $navigationLabel = 'Intentos';

    protected static ?string $title = 'Evaluaciones rendidas';

    protected static ?string $breadcrumb = 'Intentos';

    protected string $view = 'filament-panels::pages.page';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        static::authorizeResourceAccess();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => QuizAttempt::query()
                ->whereIn('quiz_id', $this->evaluacionesDelCurso())
                ->with(['quiz.class.module', 'quiz.module', 'student.user']))
            ->modelLabel('intento')
            ->pluralModelLabel('intentos')
            ->defaultSort('started_at', 'desc')
            ->columns([
                TextColumn::make('student.user.full_name')
                    ->label('Alumno')
                    ->searchable(['users.first_name', 'users.last_name'])
                    ->sortable(),

                TextColumn::make('evaluacion')
                    ->label('Evaluación')
                    ->state(fn (QuizAttempt $record): string => $record->quiz?->isModuleExam()
                        ? 'Examen · '.($record->quiz->module?->title ?? '')
                        : ($record->quiz?->class?->title ?? ''))
                    ->description(fn (QuizAttempt $record): ?string => $record->quiz?->isModuleExam()
                        ? null
                        : 'Autoevaluación')
                    ->wrap(),

                TextColumn::make('attempt_number')
                    ->label('Intento')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('submitted_at')
                    ->label('Entregado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('En curso')
                    ->sortable(),

                TextColumn::make('score')
                    ->label('Nota')
                    ->alignCenter()
                    ->placeholder('—')
                    ->suffix(fn (?int $state): string => $state === null ? '' : '%')
                    ->sortable(),

                IconColumn::make('passed')
                    ->label('Aprobó')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('passed')
                    ->label('Resultado')
                    ->placeholder('Todos')
                    ->trueLabel('Sólo aprobados')
                    ->falseLabel('Sólo desaprobados'),

                TernaryFilter::make('entregado')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Entregados')
                    ->falseLabel('En curso')
                    ->queries(
                        true: fn (Builder $q): Builder => $q->whereNotNull('submitted_at'),
                        false: fn (Builder $q): Builder => $q->whereNull('submitted_at'),
                    ),

                SelectFilter::make('clase')
                    ->label('Clase')
                    ->options(fn (): array => CourseClass::query()
                        ->whereIn('module_id', $this->getCourse()->modules()->select('id'))
                        ->orderBy('order_number')
                        ->pluck('title', 'id')
                        ->all())
                    ->query(fn (Builder $q, array $data): Builder => filled($data['value'] ?? null)
                        ? $q->whereIn('quiz_id', Quiz::where('class_id', $data['value'])->select('id'))
                        : $q),
            ])
            ->recordActions([
                Action::make('respuestas')
                    ->label('Ver respuestas')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (QuizAttempt $record): string => 'Intento de '.($record->student?->user?->full_name ?? ''))
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    // Sólo tiene sentido en los entregados: uno en curso todavía
                    // no tiene respuestas guardadas
                    ->visible(fn (QuizAttempt $record): bool => $record->isSubmitted())
                    ->modalContent(fn (QuizAttempt $record): View => view('filament.attempt-answers', [
                        'attempt' => $record->load(['answers.question.options', 'answers.selectedOption', 'quiz']),
                    ])),
            ])
            ->emptyStateHeading('Todavía no rindió nadie')
            ->emptyStateDescription('Acá aparecen las autoevaluaciones y los exámenes que rindan los alumnos del curso.');
    }

    /** @return Builder<Quiz> las evaluaciones del curso: de clase y de módulo */
    private function evaluacionesDelCurso(): Builder
    {
        $modulos = $this->getCourse()->modules()->select('id');

        return Quiz::query()
            ->where(fn (Builder $q): Builder => $q
                ->whereIn('module_id', $modulos)
                ->orWhereIn('class_id', CourseClass::query()->whereIn('module_id', $modulos)->select('id')))
            ->select('id');
    }

    private function getCourse(): Course
    {
        return $this->getRecord();
    }
}
