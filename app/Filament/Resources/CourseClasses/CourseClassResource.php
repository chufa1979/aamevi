<?php

namespace App\Filament\Resources\CourseClasses;

use Filament\Tables\Table;
use App\Models\CourseClass;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use App\Filament\Resources\CourseClasses\Pages\EditCourseClass;
use App\Filament\Resources\CourseClasses\Pages\ListCourseClasses;
use App\Filament\Resources\CourseClasses\RelationManagers\QuestionsRelationManager;

/**
 * Pantalla propia de la clase, para administrar su evaluación y su banco de
 * preguntas. Igual que CourseModuleResource, existe solo porque un relation
 * manager no puede anidar otro: no aparece en la navegación ni permite crear
 * clases sueltas — eso se hace desde el módulo.
 */
class CourseClassResource extends Resource
{
    protected static ?string $model = CourseClass::class;

    protected static ?string $modelLabel = 'clase';

    protected static ?string $pluralModelLabel = 'clases';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getRecordTitle(?Model $record): ?string
    {
        return $record?->title;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Quiz de la clase')
                ->description('Se rinde al terminar la clase, con preguntas de su propio banco.')
                ->relationship('quiz')
                ->columns(3)
                ->components([
                    TextInput::make('questions_per_student')
                        ->label('Preguntas por alumno')
                        ->numeric()
                        ->minValue(1)
                        ->default(5)
                        ->required()
                        ->helperText('Se sortean del banco de esta clase.'),

                    TextInput::make('passing_score')
                        ->label('Nota mínima')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(100)
                        ->default(70)
                        ->required()
                        ->suffix('%'),

                    TextInput::make('max_attempts')
                        ->label('Intentos')
                        ->numeric()
                        ->minValue(1)
                        ->default(3)
                        ->required(),

                    Toggle::make('randomize_options')
                        ->label('Mezclar las opciones')
                        ->default(true),

                    Toggle::make('show_correct_answers')
                        ->label('Mostrar las respuestas correctas al terminar')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('module.course.title')->label('Curso'),
            TextColumn::make('module.title')->label('Módulo'),
            TextColumn::make('title')->label('Clase'),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourseClasses::route('/'),
            'edit' => EditCourseClass::route('/{record}/edit'),
        ];
    }
}
