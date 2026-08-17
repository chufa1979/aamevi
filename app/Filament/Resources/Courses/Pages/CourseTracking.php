<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Models\Course;
use App\Enums\EnrollmentStatus;
use App\Enums\ClassProgressState;
use App\Services\ProgressService;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use App\Filament\Resources\Courses\CourseResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

/**
 * Grilla de alumnos por clases: quién va por dónde.
 *
 * FID resuelve esto con un ✔ cuando el alumno completó la autoevaluación de la
 * clase, y una consulta por celda. Acá cada celda distingue los cinco estados
 * de `ClassProgressState` y todo sale de `ProgressService::courseMatrix()`, que
 * lo arma con tres consultas.
 */
class CourseTracking extends Page
{
    use InteractsWithRecord;

    protected static string $resource = CourseResource::class;

    protected static ?string $navigationLabel = 'Seguimiento';

    protected static ?string $title = 'Seguimiento de alumnos';

    protected string $view = 'filament.pages.course-tracking';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        static::authorizeResourceAccess();
    }

    /**
     * @return array{
     *     modules: Collection,
     *     alumnos: Collection,
     *     grilla: array<string, array<string, ClassProgressState>>,
     *     avances: array<string, int>,
     *     totalClases: int,
     * }
     */
    public function getViewData(): array
    {
        $course = $this->getCourse();
        $progreso = app(ProgressService::class);

        $modules = $course->modules()->with('classes')->get();

        $alumnos = $course->enrollments()
            ->with('student.user')
            ->whereIn('status', [
                EnrollmentStatus::Approved,
                EnrollmentStatus::Active,
                EnrollmentStatus::Completed,
            ])
            ->get()
            ->sortBy(fn ($e): string => $e->student?->user?->last_name ?? '')
            ->values();

        $grilla = $progreso->courseMatrix($course);
        $totalClases = $modules->sum(fn ($m): int => $m->classes->count());

        // El porcentaje sale de la misma grilla: pedirle courseProgress() al
        // servicio por alumno serían dos consultas más por fila.
        $avances = $alumnos
            ->mapWithKeys(function ($enrollment) use ($grilla, $totalClases): array {
                $estados = $grilla[$enrollment->student_id] ?? [];
                $aprobadas = count(array_filter(
                    $estados,
                    fn (ClassProgressState $e): bool => $e === ClassProgressState::Completed,
                ));

                return [$enrollment->student_id => $totalClases === 0
                    ? 0
                    : (int) round($aprobadas / $totalClases * 100)];
            })
            ->all();

        return [
            'modules' => $modules,
            'alumnos' => $alumnos,
            'grilla' => $grilla,
            'avances' => $avances,
            'totalClases' => $totalClases,
        ];
    }

    private function getCourse(): Course
    {
        return $this->getRecord();
    }
}
