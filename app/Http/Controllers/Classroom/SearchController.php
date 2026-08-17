<?php

namespace App\Http\Controllers\Classroom;

use Illuminate\View\View;
use App\Models\CourseClass;
use Illuminate\Http\Request;
use App\Services\SearchService;
use App\Http\Controllers\Controller;

/** El buscador del encabezado. */
class SearchController extends Controller
{
    public function index(Request $request, SearchService $buscador): View
    {
        $student = $request->user()->student;
        $termino = trim((string) $request->query('q', ''));

        $resultados = $buscador->forStudent($student, $termino);

        /*
         * El estado de cada clase se resuelve acá y no en la vista: es una
         * consulta por clase y una plantilla no es lugar para eso.
         */
        $estados = $resultados['clases']->mapWithKeys(
            fn (CourseClass $c): array => [$c->getKey() => $buscador->estadoDe($student, $c)],
        );

        return view('classroom.search', [
            'termino' => $termino,
            'cursos' => $resultados['cursos'],
            'clases' => $resultados['clases'],
            'estados' => $estados,
            'minimo' => SearchService::MINIMO,
        ]);
    }
}
