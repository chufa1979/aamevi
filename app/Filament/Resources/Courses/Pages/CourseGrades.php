<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Models\Course;
use Filament\Tables\Table;
use App\Models\CourseClass;
use App\Models\ClassContent;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use App\Models\TaskSubmission;
use App\Enums\SubmissionStatus;
use Filament\Actions\BulkAction;
use Filament\Resources\Pages\Page;
use App\Services\SubmissionService;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Toggle;
use App\Exceptions\SubmissionException;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Collection;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Filament\Resources\Courses\CourseResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

/**
 * Las entregas del curso: corregirlas y publicarlas.
 *
 * Corregir y publicar son dos pasos a propósito. El docente corrige a lo largo
 * de la semana y suelta la tanda entera cuando terminó, en vez de que las notas
 * le vayan apareciendo al alumno de a una según el orden en que corrija. Lo que
 * vuelve usable esa separación es la acción masiva de abajo: sin ella, publicar
 * de a una sería una molestia y nadie lo usaría.
 */
class CourseGrades extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = CourseResource::class;

    protected static ?string $navigationLabel = 'Calificaciones';

    protected static ?string $title = 'Calificaciones del curso';

    protected static ?string $breadcrumb = 'Calificaciones';

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
            ->query(fn (): Builder => TaskSubmission::query()
                ->whereIn('content_id', $this->tareasDelCurso())
                ->with(['content.class.module', 'student.user', 'gradedBy.user']))
            ->modelLabel('entrega')
            ->pluralModelLabel('entregas')
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('content.class.title')
                    ->label('Clase')
                    ->description(fn (TaskSubmission $record): string => $record->content?->title ?? '')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('student.user.full_name')
                    ->label('Alumno')
                    ->searchable(['users.first_name', 'users.last_name'])
                    ->sortable(),

                TextColumn::make('submitted_at')
                    ->label('Entregada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('attempt_number')
                    ->label('Nº')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('grade')
                    ->label('Nota')
                    ->alignCenter()
                    ->placeholder('—')
                    ->formatStateUsing(fn (?string $state): string => $state === null
                        ? '—'
                        : rtrim(rtrim($state, '0'), '.')),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),

                IconColumn::make('published_at')
                    ->label('Publicada')
                    ->boolean()
                    ->tooltip(fn (TaskSubmission $record): ?string => $record->isPublished()
                        ? null
                        : 'El alumno todavía no ve la nota'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(SubmissionStatus::class),

                TernaryFilter::make('publicada')
                    ->label('Publicación')
                    ->placeholder('Todas')
                    ->trueLabel('Publicadas')
                    ->falseLabel('Sin publicar')
                    ->queries(
                        true: fn (Builder $q): Builder => $q->whereNotNull('published_at'),
                        false: fn (Builder $q): Builder => $q->whereNull('published_at'),
                    ),

                SelectFilter::make('class')
                    ->label('Clase')
                    ->options(fn (): array => CourseClass::query()
                        ->whereIn('module_id', $this->getCourse()->modules()->select('id'))
                        ->orderBy('order_number')
                        ->pluck('title', 'id')
                        ->all())
                    ->query(fn (Builder $q, array $data): Builder => filled($data['value'] ?? null)
                        ? $q->whereIn('content_id', ClassContent::where('class_id', $data['value'])->select('id'))
                        : $q),
            ])
            ->recordActions([
                Action::make('descargar')
                    ->label('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (TaskSubmission $record): ?string => $record->url())
                    ->openUrlInNewTab()
                    ->visible(fn (TaskSubmission $record): bool => $record->url() !== null),

                $this->corregir(),
                $this->publicar(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    $this->publicarEnTanda(),
                ]),
            ])
            ->emptyStateHeading('Todavía no hay entregas')
            ->emptyStateDescription('Aparecen acá cuando un alumno sube el archivo de un trabajo práctico.');
    }

    /** Poner nota, resultado y devolución. Una vez hecho no se rehace. */
    private function corregir(): Action
    {
        return Action::make('corregir')
            ->label('Corregir')
            ->icon('heroicon-o-pencil-square')
            ->visible(fn (TaskSubmission $record): bool => ! $record->isGraded())
            ->schema([
                TextInput::make('nota')
                    ->label('Nota')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(10)
                    ->step(0.5)
                    ->required()
                    ->helperText('De 1 a 10.'),

                Toggle::make('aprobada')
                    ->label('Aprobada')
                    ->default(true)
                    ->helperText('Si la desaprobás, el alumno va a poder volver a entregar.'),

                Textarea::make('devolucion')
                    ->label('Devolución')
                    ->rows(4)
                    ->helperText('Lo que va a leer el alumno cuando se publique la nota.'),
            ])
            ->action(function (TaskSubmission $record, array $data): void {
                try {
                    app(SubmissionService::class)->grade(
                        $record,
                        auth()->user()->teacher,
                        (float) $data['nota'],
                        (bool) $data['aprobada'],
                        $data['devolucion'] ?? null,
                    );
                } catch (SubmissionException $e) {
                    Notification::make()->title('No se pudo corregir')->body($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()
                    ->title('Corregida')
                    ->body('Todavía no la ve el alumno: falta publicarla.')
                    ->success()
                    ->send();
            });
    }

    private function publicar(): Action
    {
        return Action::make('publicar')
            ->label('Publicar')
            ->icon('heroicon-o-eye')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('A partir de ahora el alumno va a ver la nota y la devolución.')
            ->visible(fn (TaskSubmission $record): bool => $record->isGraded() && ! $record->isPublished())
            ->action(function (TaskSubmission $record): void {
                app(SubmissionService::class)->publish($record);

                Notification::make()->title('Nota publicada')->success()->send();
            });
    }

    /** Lo que hace que separar corregir de publicar no moleste. */
    private function publicarEnTanda(): BulkAction
    {
        return BulkAction::make('publicarSeleccionadas')
            ->label('Publicar las corregidas')
            ->icon('heroicon-o-eye')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('Se publican sólo las que ya estén corregidas; las demás quedan como están.')
            ->action(function (Collection $records): void {
                $publicadas = 0;

                foreach ($records as $record) {
                    if ($record->isGraded() && ! $record->isPublished()) {
                        app(SubmissionService::class)->publish($record);
                        $publicadas++;
                    }
                }

                Notification::make()
                    ->title($publicadas === 0 ? 'No había nada para publicar' : "Se publicaron {$publicadas} notas")
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    /** @return Builder<ClassContent> las tareas del curso */
    private function tareasDelCurso(): Builder
    {
        return ClassContent::query()
            ->whereIn('class_id', CourseClass::query()
                ->whereIn('module_id', $this->getCourse()->modules()->select('id'))
                ->select('id'))
            ->select('id');
    }

    private function getCourse(): Course
    {
        return $this->getRecord();
    }
}
