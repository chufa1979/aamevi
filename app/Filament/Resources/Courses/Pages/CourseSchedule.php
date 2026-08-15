<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Models\Course;
use Filament\Tables\Table;
use App\Models\CourseClass;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\Page;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use App\Filament\Actions\ShiftClassDates;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Filament\Resources\Courses\CourseResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use App\Filament\Resources\CourseClasses\CourseClassResource;
use App\Filament\Resources\CourseModules\CourseModuleResource;

/**
 * Cronograma del curso: todas sus clases, de todos sus módulos, en una sola
 * lista ordenada por fecha.
 *
 * La estructura se arma en Contenidos; acá se ve y se mueve en el tiempo. Es la
 * pantalla que responde "qué se dicta la semana que viene", que mirando módulo
 * por módulo no se contesta.
 */
class CourseSchedule extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = CourseResource::class;

    protected static ?string $navigationLabel = 'Planificación';

    protected static ?string $title = 'Planificación del curso';

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
            ->query(fn (): Builder => $this->getCourse()->classes()->getQuery()->with('module'))
            ->recordTitleAttribute('title')
            ->defaultSort('activation_date')
            ->columns([
                TextColumn::make('module.order_number')
                    ->label('Mód.')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('module.title')
                    ->label('Módulo')
                    ->wrap()
                    ->toggleable(),

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
                    ->color(fn (CourseClass $record): string => $record->isAvailable() ? 'success' : 'warning'),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->state(fn (CourseClass $record): string => $record->isAvailable() ? 'Disponible' : 'Pendiente')
                    ->badge()
                    ->color(fn (CourseClass $record): string => $record->isAvailable() ? 'success' : 'gray'),

                TextColumn::make('contents_count')
                    ->label('Material')
                    ->counts('contents')
                    ->alignCenter(),

                IconColumn::make('is_live_session')
                    ->label('En vivo')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('module_id')
                    ->label('Módulo')
                    ->options(fn (): array => $this->getCourse()
                        ->modules()
                        ->pluck('title', 'id')
                        ->all()),

                TernaryFilter::make('disponible')
                    ->label('Disponibilidad')
                    ->placeholder('Todas')
                    ->trueLabel('Ya habilitadas')
                    ->falseLabel('Todavía no')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->where('activation_date', '<=', now()),
                        false: fn (Builder $query): Builder => $query->where('activation_date', '>', now()),
                    ),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (CourseClass $record): string => CourseModuleResource::getUrl('classes', ['record' => $record->module_id])),

                Action::make('evaluation')
                    ->label('Evaluación')
                    ->icon('heroicon-o-question-mark-circle')
                    ->url(fn (CourseClass $record): string => CourseClassResource::getUrl('edit', ['record' => $record])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ShiftClassDates::make(),
                ]),
            ])
            ->emptyStateHeading('Este curso todavía no tiene clases')
            ->emptyStateDescription('Se cargan desde la solapa Contenidos, dentro de cada módulo.');
    }

    private function getCourse(): Course
    {
        return $this->getRecord();
    }
}
