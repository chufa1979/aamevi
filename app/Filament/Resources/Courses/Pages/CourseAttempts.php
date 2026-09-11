<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Models\Quiz;
use App\Models\Course;
use Filament\Tables\Table;
use App\Models\CourseClass;
use App\Models\QuizAttempt;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use App\Services\QuizService;
use Filament\Resources\Pages\Page;
use Filament\Tables\Filters\Filter;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Filament\Resources\Courses\CourseResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

/**
 * Cómo le fue a cada alumno en cada evaluación, y qué hacer con el que se trabó.
 *
 * **Una fila por alumno y evaluación**, no por intento. Con veinte alumnos y
 * veinticinco clases, el registro plano eran cientos de filas donde lo único que
 * hay que ver —quién está trabado— quedaba enterrado.
 *
 * La fila es un intento real —el último de ese par— para que las acciones tengan
 * un registro con identidad; el resumen sale de subconsultas correlacionadas y
 * no de una consulta por fila, que con diez filas por página serían treinta
 * consultas de más.
 *
 * Los datos estaban guardados desde el principio —`quiz_question_assignment`
 * registra qué preguntas le tocaron a cada uno, y `student_answers` qué eligió—
 * pero no había forma de mirarlos ni de destrabar a nadie.
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

    /** Cuántos alumnos están trabados en este curso: es la razón de entrar acá. */
    public static function getNavigationBadge(): ?string
    {
        $course = Course::find(request()->route('record'));

        if ($course === null) {
            return null;
        }

        $trabados = QuizAttempt::ultimosPorEvaluacion(self::evaluacionesDe($course))->trabados()->count();

        return $trabados > 0 ? (string) $trabados : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

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
            ->query(fn (): Builder => QuizAttempt::ultimosPorEvaluacion($this->evaluacionesDelCurso())
                ->with(['quiz.class.module', 'quiz.module', 'student.user']))
            ->modelLabel('evaluación rendida')
            ->pluralModelLabel('evaluaciones rendidas')
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

                TextColumn::make('usados')
                    ->label('Intentos')
                    ->alignCenter()
                    ->state(fn (QuizAttempt $record): string => $record->usados.' de '.($record->quiz?->max_attempts ?? 0))
                    // Los anteriores a un reseteo siguen en el historial pero no
                    // ocupan cupo: conviene decirlo donde se ve el número
                    ->description(fn (QuizAttempt $record): ?string => $record->attempt_number > $record->usados
                        ? $record->attempt_number.' en total'
                        : null),

                TextColumn::make('score')
                    ->label('Última nota')
                    ->alignCenter()
                    ->placeholder('En curso')
                    ->suffix(fn (?int $state): string => $state === null ? '' : '%')
                    ->sortable(),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->state(fn (QuizAttempt $record): string => $this->estado($record))
                    ->color(fn (QuizAttempt $record): string => match (true) {
                        $record->aprobado > 0 => 'success',
                        $record->attemptsLeft() === 0 => 'danger',
                        default => 'warning',
                    }),

                TextColumn::make('submitted_at')
                    ->label('Última vez')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                /*
                 * El filtro que convierte la pantalla en una lista de trabajo:
                 * quién está trabado y necesita que alguien haga algo.
                 */
                /*
                 * El parámetro se llama `$query` a propósito: Filament resuelve
                 * los argumentos del closure **por nombre**, así que uno llamado
                 * `$q` no recibe nada y el filtro no filtra — sin error.
                 */
                Filter::make('trabados')
                    ->label('Sólo los que se quedaron sin intentos')
                    ->query(fn (Builder $query): Builder => $query->trabados()),

                SelectFilter::make('clase')
                    ->label('Clase')
                    ->options(fn (): array => CourseClass::query()
                        ->whereIn('module_id', $this->getCourse()->modules()->select('id'))
                        ->orderBy('order_number')
                        ->pluck('title', 'id')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereIn('quiz_id', Quiz::where('class_id', $data['value'])->select('id'))
                        : $query),
            ])
            ->recordActions([
                Action::make('historial')
                    ->label('Ver historial')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (QuizAttempt $record): string => 'Intentos de '.($record->student?->user?->full_name ?? ''))
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(fn (QuizAttempt $record): View => view('filament.attempt-history', [
                        'intentos' => $record->hermanos(),
                        'quiz' => $record->quiz,
                    ])),

                self::resetear(),
            ])
            ->emptyStateHeading('Todavía no rindió nadie')
            ->emptyStateDescription('Acá aparecen las autoevaluaciones y los exámenes que rindan los alumnos del curso.');
    }

    /**
     * Devolverle los intentos a un alumno.
     *
     * Sólo aparece donde hace falta: agotados y sin aprobar. Si aprobó no hay
     * nada que destrabar, y si le quedan intentos puede rendir solo.
     */
    private static function resetear(): Action
    {
        return Action::make('resetear')
            ->label('Devolver intentos')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Devolver los intentos')
            ->modalDescription(fn (QuizAttempt $record): string => 'El alumno vuelve a tener sus '
                .($record->quiz?->max_attempts ?? 0).' intentos. Los anteriores quedan en el historial.')
            ->schema([
                Textarea::make('motivo')
                    ->label('Motivo')
                    ->helperText('Opcional. Queda registrado y se le muestra al alumno en el aviso.')
                    ->rows(3),
            ])
            ->visible(fn (QuizAttempt $record): bool => $record->aprobado === 0
                && $record->attemptsLeft() === 0)
            ->action(function (QuizAttempt $record, array $data): void {
                if ($record->quiz === null || $record->student === null) {
                    return;
                }

                app(QuizService::class)->reset(
                    $record->quiz,
                    $record->student,
                    auth()->user(),
                    $data['motivo'] ?? null,
                );

                Notification::make()
                    ->title('Intentos devueltos')
                    ->body('Se le encoló el aviso por email.')
                    ->success()
                    ->send();
            });
    }

    private function estado(QuizAttempt $record): string
    {
        if ($record->aprobado > 0) {
            return 'Aprobada';
        }

        $quedan = $record->attemptsLeft();

        return match (true) {
            $quedan === 0 => 'Sin intentos',
            $quedan === 1 => 'Le queda 1',
            default => "Le quedan {$quedan}",
        };
    }

    /** @return Builder<Quiz> las evaluaciones del curso: de clase y de módulo */
    private static function evaluacionesDe(Course $course): Builder
    {
        $modulos = $course->modules()->select('id');

        return Quiz::query()
            ->where(fn (Builder $q): Builder => $q
                ->whereIn('module_id', $modulos)
                ->orWhereIn('class_id', CourseClass::query()->whereIn('module_id', $modulos)->select('id')))
            ->select('id');
    }

    /** @return Builder<Quiz> */
    private function evaluacionesDelCurso(): Builder
    {
        return self::evaluacionesDe($this->getCourse());
    }

    private function getCourse(): Course
    {
        return $this->getRecord();
    }
}
