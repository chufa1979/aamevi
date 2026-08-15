<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Models\Quiz;
use App\Models\Course;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Filament\Resources\Courses\CourseResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use App\Filament\Resources\CourseModules\CourseModuleResource;

/**
 * Los exámenes de módulo del curso.
 *
 * Cada examen sortea un porcentaje del banco combinado de todas las clases de
 * su módulo. La columna que importa es la última: un examen cuyo banco está
 * vacío se aprueba solo, y sin esta pantalla no había forma de enterarse hasta
 * que lo rindiera un alumno.
 *
 * Las autoevaluaciones de clase no se listan acá: cuelgan de cada clase y se
 * configuran en su pantalla.
 */
class CourseExams extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = CourseResource::class;

    protected static ?string $navigationLabel = 'Exámenes';

    protected static ?string $title = 'Exámenes de módulo';

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
            ->query(fn (): Builder => Quiz::query()
                ->whereIn('module_id', $this->getCourse()->modules()->select('id'))
                ->with('module'))
            ->recordTitleAttribute('title')
            ->defaultSort('module_id')
            ->columns([
                TextColumn::make('module.order_number')
                    ->label('#')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('module.title')
                    ->label('Módulo')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('questions_percentage')
                    ->label('Porcentaje')
                    ->suffix('%')
                    ->alignCenter(),

                TextColumn::make('banco')
                    ->label('Banco')
                    ->state(fn (Quiz $record): int => $record->poolSize())
                    ->alignCenter()
                    ->description('preguntas cargadas'),

                TextColumn::make('sortea')
                    ->label('Sortea')
                    ->state(fn (Quiz $record): int => $record->questionsToDraw())
                    ->alignCenter()
                    ->description('por alumno'),

                TextColumn::make('passing_score')
                    ->label('Nota mínima')
                    ->suffix('%')
                    ->alignCenter(),

                TextColumn::make('max_attempts')
                    ->label('Intentos')
                    ->alignCenter(),

                TextColumn::make('listo')
                    ->label('Estado')
                    ->badge()
                    ->state(fn (Quiz $record): string => $record->isReady()
                        ? 'Listo'
                        : 'Sin preguntas')
                    ->color(fn (Quiz $record): string => $record->isReady() ? 'success' : 'danger')
                    ->tooltip(fn (Quiz $record): ?string => $record->isReady()
                        ? null
                        : 'Ninguna clase del módulo tiene preguntas activas: el examen se aprobaría sin responder nada.'),
            ])
            ->recordActions([
                Action::make('configure')
                    ->label('Configurar')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->url(fn (Quiz $record): string => CourseModuleResource::getUrl('edit', ['record' => $record->module_id])),

                Action::make('questions')
                    ->label('Ver clases')
                    ->icon('heroicon-o-academic-cap')
                    ->url(fn (Quiz $record): string => CourseModuleResource::getUrl('classes', ['record' => $record->module_id])),
            ])
            ->emptyStateHeading('Ningún módulo de este curso tiene examen')
            ->emptyStateDescription('El examen es opcional. Se habilita desde la solapa Contenidos, al editar el módulo.');
    }

    private function getCourse(): Course
    {
        return $this->getRecord();
    }
}
