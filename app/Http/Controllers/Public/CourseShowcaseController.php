<?php

namespace App\Http\Controllers\Public;

use App\Models\Course;
use Illuminate\View\View;
use App\Http\Controllers\Controller;

/**
 * Ficha pública de un curso, en `/curso/{course}` (singular).
 *
 * Es una ruta distinta de `/cursos/{course}` (plural, `classroom.course`):
 * esa es el temario privado que ve un alumno ya inscripto. Esta es la vidriera
 * que puede ver cualquiera, sin sesión — pensada para linkear desde el
 * catálogo público del home y, eventualmente, desde fuera del sitio.
 */
class CourseShowcaseController extends Controller
{
    public function show(Course $course): View
    {
        // Un curso inactivo no está en oferta: no tiene ficha pública, ni
        // siquiera para quien tenga el enlace directo.
        abort_unless($course->is_active, 404);

        $course->load(['teacher.user', 'modules']);

        return view('public.course', ['course' => $course]);
    }
}
