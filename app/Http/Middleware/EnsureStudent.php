<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * El aula es para alumnos.
 *
 * Todas las pantallas del aula trabajan sobre la ficha de alumno —inscripciones,
 * avance, intentos—, que comparte la clave con el usuario pero puede no existir:
 * un administrador o un docente no la tienen. Sin este corte, cada controlador
 * tendría que defenderse por su cuenta de un `null`.
 *
 * Después de este middleware, `$request->user()->student` nunca es null.
 */
class EnsureStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->student === null) {
            abort(403, 'El aula es para alumnos: tu cuenta no tiene ficha de alumno.');
        }

        return $next($request);
    }
}
