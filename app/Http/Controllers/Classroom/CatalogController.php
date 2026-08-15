<?php

namespace App\Http\Controllers\Classroom;

use App\Models\Course;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Services\EnrollmentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Exceptions\EnrollmentException;

/** Los cursos a los que el alumno todavía puede anotarse. */
class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user()->student;

        return view('classroom.catalog', [
            'cursos' => Course::availableFor($student)
                ->with('teacher.user')
                ->withCount('modules')
                ->orderBy('title')
                ->get(),
        ]);
    }

    public function store(Request $request, Course $course, EnrollmentService $inscripciones): RedirectResponse
    {
        try {
            $inscripciones->request($request->user()->student, $course);
        } catch (EnrollmentException $e) {
            // Las reglas viven en el servicio; acá sólo se traducen a un mensaje
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('classroom.courses')
            ->with('exito', "Pediste inscribirte a «{$course->title}». Te avisamos cuando la aprueben.");
    }
}
