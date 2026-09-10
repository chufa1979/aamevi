<?php

namespace App\Http\Controllers\Public;

use App\Models\Course;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

/**
 * El «/» ya no vive detrás de `auth`: es la vidriera pública de la plataforma.
 *
 * Quien tiene sesión sigue viendo el dashboard de siempre — los accesos por
 * rol de `home.blade.php` — porque para esa persona `/` es su punto de
 * partida dentro del sistema, no una landing. Quien no la tiene ve el
 * catálogo de cursos activos, que es lo que puede ofrecerle la plataforma
 * antes de pedirle que se registre.
 */
class HomeController extends Controller
{
    public function index(Request $request): View
    {
        if ($request->user() !== null) {
            return view('home');
        }

        return view('public.home', [
            'cursos' => Course::query()
                ->where('is_active', true)
                ->with('teacher.user')
                ->withCount('modules')
                ->orderBy('title')
                ->get(),
        ]);
    }
}
