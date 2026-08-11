<?php

namespace App\Filament\Resources\CourseModules;

use Filament\Tables\Table;
use App\Models\CourseModule;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use App\Filament\Resources\CourseModules\Pages\EditCourseModule;
use App\Filament\Resources\CourseModules\Pages\ListCourseModules;
use App\Filament\Resources\CourseModules\RelationManagers\ClassesRelationManager;

/**
 * Los módulos se administran desde el curso; este recurso existe para poder
 * abrir uno y trabajar sus clases, porque un relation manager no puede anidar
 * otro. Por eso no aparece en la navegación ni permite crear sueltos.
 */
class CourseModuleResource extends Resource
{
    protected static ?string $model = CourseModule::class;

    protected static ?string $modelLabel = 'módulo';

    protected static ?string $pluralModelLabel = 'módulos';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getRecordTitle(?Model $record): ?string
    {
        return $record?->title;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->components([
                    TextInput::make('title')
                        ->label('Título')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('order_number')
                        ->label('Orden')
                        ->numeric()
                        ->minValue(1)
                        ->required(),

                    Textarea::make('description')
                        ->label('Descripción')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('course.title')->label('Curso'),
                TextColumn::make('order_number')->label('#'),
                TextColumn::make('title')->label('Módulo'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ClassesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourseModules::route('/'),
            'edit' => EditCourseModule::route('/{record}/edit'),
        ];
    }
}
