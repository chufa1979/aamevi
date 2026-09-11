<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Courses\CourseResource;

/**
 * Cursos activos donde el docente no tocó nada hace rato: ni contenido nuevo
 * ni una corrección. Cargar material o corregir entregas pasa al ritmo de cada
 * docente, pero treinta días sin ninguna de las dos es la señal de que un curso
 * quedó atrás.
 */
class CoursesWithoutActivity extends TableWidget
{
    protected static ?int $sort = 4;

    private const DIAS_SIN_ACTIVIDAD = 30;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Course::query()
                ->where('is_active', true)
                ->whereNotExists(fn ($q) => $q->selectRaw('1')
                    ->from('modules')
                    ->whereColumn('modules.course_id', 'courses.id')
                    ->where('modules.updated_at', '>=', now()->subDays(self::DIAS_SIN_ACTIVIDAD)))
                ->whereNotExists(fn ($q) => $q->selectRaw('1')
                    ->from('classes')
                    ->join('modules', 'modules.id', '=', 'classes.module_id')
                    ->whereColumn('modules.course_id', 'courses.id')
                    ->where('classes.updated_at', '>=', now()->subDays(self::DIAS_SIN_ACTIVIDAD)))
                ->whereNotExists(fn ($q) => $q->selectRaw('1')
                    ->from('task_submissions')
                    ->join('class_content', 'class_content.id', '=', 'task_submissions.content_id')
                    ->join('classes', 'classes.id', '=', 'class_content.class_id')
                    ->join('modules', 'modules.id', '=', 'classes.module_id')
                    ->whereColumn('modules.course_id', 'courses.id')
                    ->where('task_submissions.graded_at', '>=', now()->subDays(self::DIAS_SIN_ACTIVIDAD)))
                // Cada máximo se correlaciona por separado contra la tabla real
                // `courses`: un `GREATEST` de los tres en SQL exigiría envolverlos
                // en una subconsulta derivada, y ahí `courses.id` deja de estar a
                // la vista — MySQL la rechaza con «Unknown column». Combinarlos en
                // PHP evita ese problema y es portable a sqlite.
                ->addSelect([
                    'modulos_max' => DB::table('modules')
                        ->selectRaw('max(modules.updated_at)')
                        ->whereColumn('modules.course_id', 'courses.id'),

                    'clases_max' => DB::table('classes')
                        ->join('modules', 'modules.id', '=', 'classes.module_id')
                        ->selectRaw('max(classes.updated_at)')
                        ->whereColumn('modules.course_id', 'courses.id'),

                    'entregas_max' => DB::table('task_submissions')
                        ->join('class_content', 'class_content.id', '=', 'task_submissions.content_id')
                        ->join('classes', 'classes.id', '=', 'class_content.class_id')
                        ->join('modules', 'modules.id', '=', 'classes.module_id')
                        ->selectRaw('max(task_submissions.graded_at)')
                        ->whereColumn('modules.course_id', 'courses.id'),
                ])
                ->orderBy('title')
                ->with('teacher.user'))
            ->heading('Cursos sin actividad reciente')
            ->description('Activos, sin contenido nuevo ni correcciones en los últimos '.self::DIAS_SIN_ACTIVIDAD.' días')
            ->paginated([5])
            ->columns([
                TextColumn::make('title')
                    ->label('Curso'),

                TextColumn::make('teacher.user.full_name')
                    ->label('Docente')
                    ->placeholder('— sin asignar —'),

                TextColumn::make('ultima_actividad')
                    ->label('Última actividad')
                    ->state(fn (Course $record): ?string => collect([
                        $record->modulos_max,
                        $record->clases_max,
                        $record->entregas_max,
                    ])->filter()->max())
                    ->dateTime('d/m/Y')
                    ->placeholder('Nunca'),
            ])
            ->recordActions([
                Action::make('ver')
                    ->label('Ver curso')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Course $record): string => CourseResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('Todos los cursos activos tuvieron actividad reciente')
            ->emptyStateDescription('Cuando uno pase '.self::DIAS_SIN_ACTIVIDAD.' días sin contenido nuevo ni correcciones, va a aparecer acá.');
    }
}
