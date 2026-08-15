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

    protected static ?string $modelLabel = 'módulo';

    protected static ?string $pluralModelLabel = 'módulos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label('Título')
                ->required()
                ->maxLength(255),

            TextInput::make('order_number')
                ->label('Orden')
                ->numeric()
                ->minValue(1)
                ->required()
                // Sugiere la posición siguiente; el unique (course_id,
                // order_number) rechaza los repetidos.
                ->default(fn (): int => $this->getOwnerRecord()->modules()->max('order_number') + 1)
                ->helperText('Define la secuencia dentro del curso.'),

            RichText::make('description')
                ->label('Descripción')
                ->columnSpanFull(),

            ModuleExam::section()->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('order_number')
            ->columns([
                TextColumn::make('order_number')
                    ->label('#')
                    ->alignCenter()
                    ->sortable(),

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
