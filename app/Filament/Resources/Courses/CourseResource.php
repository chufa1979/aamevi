<?php

namespace App\Filament\Resources\Courses;

use App\Models\Course;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Resources\Pages\Page;
use Filament\Navigation\NavigationItem;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Concerns\ScopedToOwnCourses;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Concerns\ListAndCreateNavigation;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\Pages\CourseExams;
use App\Filament\Resources\Courses\Pages\ListCourses;
use App\Filament\Resources\Courses\Pages\CourseGrades;
use App\Filament\Resources\Courses\Pages\CreateCourse;
use App\Filament\Resources\Courses\Schemas\CourseForm;
use App\Filament\Resources\Courses\Tables\CoursesTable;
use App\Filament\Resources\Courses\Pages\CourseAttempts;
use App\Filament\Resources\Courses\Pages\CourseSchedule;
use App\Filament\Resources\Courses\Pages\CourseTracking;
use App\Filament\Resources\Courses\Pages\ManageCourseContent;
use App\Filament\Resources\Courses\Pages\ManageCourseStudents;

class CourseResource extends Resource
{
    use ListAndCreateNavigation;
    use ScopedToOwnCourses;

    protected static ?string $model = Course::class;

    // Sin icono propio: lo lleva el grupo, ver AdminPanelProvider
    protected static ?string $navigationLabel = 'Cursos';

    protected static ?string $modelLabel = 'curso';

    protected static ?string $pluralModelLabel = 'cursos';

    protected static string|\UnitEnum|null $navigationGroup = 'Cursos';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getRecordTitle(?Model $record): ?string
    {
        return $record?->title;
    }

    public static function form(Schema $schema): Schema
    {
        return CourseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CoursesTable::configure($table);
    }

    /**
     * Las solapas del curso, en el orden en que se trabaja: primero los datos,
     * después el cronograma y el material, después la evaluación, y al final el
     * alumnado.
     *
     * Calificaciones, Comunicación y Consultas a mesa de ayuda todavía no
     * existen; están documentadas en §13 del plan arquitectónico.
     *
     * @return array<NavigationItem>
     */
    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            EditCourse::class,
            CourseSchedule::class,
            ManageCourseContent::class,
            CourseExams::class,
            CourseAttempts::class,
            CourseGrades::class,
            ManageCourseStudents::class,
            CourseTracking::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourses::route('/'),
            'create' => CreateCourse::route('/create'),
            'edit' => EditCourse::route('/{record}/edit'),
            'schedule' => CourseSchedule::route('/{record}/planificacion'),
            'content' => ManageCourseContent::route('/{record}/contenidos'),
            'exams' => CourseExams::route('/{record}/examenes'),
            'attempts' => CourseAttempts::route('/{record}/intentos'),
            'grades' => CourseGrades::route('/{record}/calificaciones'),
            'students' => ManageCourseStudents::route('/{record}/alumnos'),
            'tracking' => CourseTracking::route('/{record}/seguimiento'),
        ];
    }
}
