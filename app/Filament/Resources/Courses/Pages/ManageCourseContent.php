<?php

namespace App\Filament\Resources\Courses\Pages;

use Filament\Tables\Table;
use App\Models\CourseModule;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use App\Filament\Forms\RichText;
use Filament\Actions\EditAction;
use App\Filament\Forms\ModuleExam;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Hidden;
use App\Filament\Tables\DragToReorder;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use App\Filament\Resources\Courses\CourseResource;
use Filament\Resources\Pages\ManageRelatedRecords;
use App\Filament\Resources\CourseModules\CourseModuleResource;

/**
 * Los módulos del curso, como solapa propia.
 *
 * Es una página y no un relation manager para que pueda tener sub-navegación
 * hacia abajo: un relation manager no puede anidar otro, y de las clases todavía
 * cuelgan el contenido y el banco de preguntas.
 */
class ManageCourseContent extends ManageRelatedRecords
{
    protected static string $resource = CourseResource::class;

    protected static string $relationship = 'modules';

    protected static ?string $navigationLabel = 'Contenidos';

    protected static ?string $title = 'Contenidos del curso';

    // Sin esto el breadcrumb muestra el nombre de la relación, en inglés
    protected static ?string $breadcrumb = 'Contenidos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label('Título')
                ->required()
                ->maxLength(255),

            // El orden se cambia arrastrando en la tabla, no escribiendo el
            // número. Un módulo nuevo va al final.
            Hidden::make('order_number')
                ->default(fn (): int => $this->getOwnerRecord()->modules()->max('order_number') + 1),

            RichText::make('description')
                ->label('Descripción')
                ->columnSpanFull(),

            ModuleExam::section()->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return DragToReorder::apply($table)
            ->recordTitleAttribute('title')
            // Los títulos de los modales salen de acá: esta clase de página no
            // lee las propiedades estáticas $modelLabel del recurso.
            ->modelLabel('módulo')
            ->pluralModelLabel('módulos')
            ->defaultSort('order_number')
            ->columns([
                TextColumn::make('order_number')
                    ->label('#')
                    ->alignCenter(),

                TextColumn::make('title')
                    ->label('Módulo')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('classes_count')
                    ->label('Clases')
                    ->counts('classes')
                    ->alignCenter(),
            ])
            ->headerActions([
                CreateAction::make()->label('Agregar módulo'),
            ])
            ->recordActions([
                Action::make('classes')
                    ->label('Clases')
                    ->icon('heroicon-o-academic-cap')
                    ->url(fn (CourseModule $record): string => CourseModuleResource::getUrl('classes', ['record' => $record])),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Este curso todavía no tiene módulos');
    }
}
