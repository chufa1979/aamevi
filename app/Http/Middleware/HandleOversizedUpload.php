<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Traduce un POST que PHP descartó por tamaño a un mensaje entendible.
 *
 * Cuando el cuerpo de la petición supera `post_max_size`, PHP la vacía **antes**
 * de que Laravel la vea: se pierden los archivos, los campos y el token CSRF. El
 * resultado es un 419 «página expirada» que no tiene nada que ver con lo que
 * pasó, y el usuario vuelve a intentar con el mismo archivo.
 *
 * Se detecta por la contradicción: vino un cuerpo con longitud declarada pero
 * llegó vacío.
 *
 * Es un parche, no la solución. Lo que corresponde es subir
 * `upload_max_filesize` y `post_max_size` en el servidor — ver docs/DEPLOY.md.
 */
class HandleOversizedUpload
{
    public function handle(Request $request, Closure $next): Response
    {
        $declarado = (int) $request->server('CONTENT_LENGTH', 0);

        $vacio = $request->isMethod('POST')
            && $declarado > 0
            && $request->post() === []
            && $request->allFiles() === [];

        if ($vacio) {
            return back()
                ->withInput()
                ->with('error', 'El archivo es demasiado grande para el servidor. Probá con uno más liviano.');
        }

        return $next($request);
    }
}
