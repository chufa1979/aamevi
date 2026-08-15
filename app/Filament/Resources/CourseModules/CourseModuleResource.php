<?php

namespace App\Filament\Resources\CourseModules;

use Filament\Tables\Table;
use App\Models\CourseModule;
use Filament\Schemas\Schema;
use App\Filament\Forms\RichText;
use Filament\Resources\Resource;
use App\Filament\Forms\ModuleExam;
use Filament\Resources\Pages\Page;
use Filament\Navigation\NavigationItem;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use App\Filament\Concerns\ScopedToOwnCourses;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Resources\CourseModules\Pages\EditCourseModule;
use App\Filament\Resources\CourseModules\Pages\ListCourseModules;
use App\Filament\Resources\CourseModules\Pages\ManageModuleClasses;

/**
 * Pantalla del módulo, con sus datos, su examen y sus clases en solapas.
 *
 * Los módulos se crean desde el curso, así que este recurso no aparece en la
 * navegación ni permite crear sueltos: se entra siempre desde la solapa
 * Contenidos del curso.
 */
class CourseModuleResource extends Resource
{
    use ScopedToOwnCourses;

    protected static ?string $model = CourseModule::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

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
        return $schema->columns(1)->components([
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

                    RichText::make('description')
                        ->label('Descripción')
                        ->columnSpanFull(),
                ]),

            ModuleExam::section(),
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

    /** @return array<NavigationItem> */
    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            EditCourseModule::class,
            ManageModuleClasses::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourseModules::route('/'),
            'edit' => EditCourseModule::route('/{record}/edit'),
            'classes' => ManageModuleClasses::route('/{record}/clases'),
        ];
    }
}
