<?php

namespace App\Http\Controllers\Classroom;

use App\Models\Course;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Services\ProgressService;
use App\Http\Controllers\Controller;
use App\Services\AnnouncementService;

/** El tablón de comunicaciones de un curso, del lado del alumno. */
class AnnouncementController extends Controller
{
    public function index(
        Request $request,
        Course $course,
        AnnouncementService $comunicaciones,
        ProgressService $progreso,
    ): View {
        $student = $request->user()->student;

        abort_unless($progreso->isEnrolled($student, $course), 403, 'No estás inscripto en este curso.');

        return view('classroom.announcements', [
            'course' => $course,
            'cursoActual' => $course,
            'comunicaciones' => $comunicaciones->forStudent($student, $course),
        ]);
    }
}
