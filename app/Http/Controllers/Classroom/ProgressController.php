<?php

namespace App\Http\Controllers\Classroom;

use Illuminate\View\View;
use App\Models\CourseClass;
use Illuminate\Http\Request;
use App\Enums\EnrollmentStatus;
use App\Models\CourseEnrollment;
use App\Enums\ClassProgressState;
use App\Services\ProgressService;
use App\Http\Controllers\Controller;

/** Dónde está parado el alumno, en todos sus cursos a la vez. */
class ProgressController extends Controller
{
    public function index(Request $request, ProgressService $progreso): View
    {
        $student = $request->user()->student;

        $cursos = $student->enrollments()
            ->whereIn('status', EnrollmentStatus::ocupantes())
            ->with('course.modules.classes')
            ->get()
            ->map(function (CourseEnrollment $enrollment) use ($progreso, $student): array {
                $course = $enrollment->course;
                $estados = $progreso->classStates($student, $course);
                $clases = $course->modules->flatMap->classes;

                return [
                    'course' => $course,
                    'avance' => $progreso->courseProgress($student, $course),
                    'total' => $clases->count(),
                    'aprobadas' => $this->contar($estados, ClassProgressState::Completed),
                    'enCurso' => $this->contar($estados, ClassProgressState::InProgress),
                    'disponibles' => $this->contar($estados, ClassProgressState::Available),
                    // La próxima que puede abrir: es la respuesta a «¿y ahora qué?»
                    'siguiente' => $clases->first(fn (CourseClass $c): bool => in_array(
                        $estados[$c->getKey()] ?? null,
                        [ClassProgressState::InProgress, ClassProgressState::Available],
                        true,
                    )),
                ];
            })
            ->sortByDesc('avance')
            ->values();

        return view('classroom.progress', ['cursos' => $cursos]);
    }

    /** @param array<string, ClassProgressState> $estados */
    private function contar(array $estados, ClassProgressState $buscado): int
    {
        return count(array_filter($estados, fn (ClassProgressState $e): bool => $e === $buscado));
    }
}
