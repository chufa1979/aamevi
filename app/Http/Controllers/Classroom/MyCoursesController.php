<?php

namespace App\Http\Controllers\Classroom;

use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Enums\EnrollmentStatus;
use App\Models\CourseEnrollment;
use App\Services\ProgressService;
use App\Http\Controllers\Controller;

/** La portada del alumno: en qué está cursando y cuánto lleva de cada cosa. */
class MyCoursesController extends Controller
{
    public function index(Request $request, ProgressService $progreso): View
    {
        // `student` garantiza que la ficha existe
        $student = $request->user()->student;

        $enrollments = $student->enrollments()
            ->with(['course.teacher.user'])
            ->get()
            ->sortBy(fn (CourseEnrollment $e): string => $e->course?->title ?? '')
            ->values();

        return view('classroom.my-courses', [
            'cursando' => $enrollments
                ->filter(fn (CourseEnrollment $e): bool => $e->status->ocupaCupo())
                ->map(fn (CourseEnrollment $e): array => [
                    'enrollment' => $e,
                    'course' => $e->course,
                    'avance' => $progreso->courseProgress($student, $e->course),
                ])
                ->values(),

            // Las pendientes y las rechazadas se muestran aparte: el alumno pidió
            // algo y necesita saber en qué quedó, aunque todavía no pueda entrar
            'sinResolver' => $enrollments
                ->filter(fn (CourseEnrollment $e): bool => in_array(
                    $e->status,
                    [EnrollmentStatus::Pending, EnrollmentStatus::Rejected],
                    true,
                ))
                ->values(),
        ]);
    }
}
