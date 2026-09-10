<?php

namespace App\Http\Controllers\Auth;

use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Auth\LoginRequest;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Rota el id de sesión para cerrar la ventana de session fixation
        $request->session()->regenerate();

        $user = $request->user();

        /*
         * El destino guardado tiene prioridad: si a alguien le pedimos la
         * contraseña camino a una clase, hay que devolverlo a esa clase.
         *
         * Pero sólo si puede llegar. Ese destino lo guardó el middleware la
         * última vez que alguien —no necesariamente esta persona— quiso abrir
         * algo sin sesión, y sobrevive en la sesión del navegador: sin el filtro,
         * un alumno que antes había tocado /admin entraba y recibía un 403.
         */
        $destino = $request->session()->pull('url.intended');

        /*
         * Segunda fuente: el widget de login del header manda `next` con la
         * página pública desde la que se abrió (por ejemplo, la ficha de un
         * curso), tomada con `url()->current()` — así que llega absoluta, no
         * como ruta relativa. Esas páginas no pasan por el middleware `auth`,
         * así que nadie guardó nada en la sesión — sin esto, alguien que se
         * loguea desde la ficha de un curso para inscribirse terminaba en su
         * home y perdía el curso que estaba mirando.
         *
         * Sólo se acepta un destino del propio sitio: un host distinto sería
         * una redirección abierta hacia fuera del sitio.
         */
        if ($destino === null) {
            $next = $request->string('next')->toString();
            $path = $next === '' ? null : parse_url($next, PHP_URL_PATH);
            $host = $next === '' ? null : parse_url($next, PHP_URL_HOST);

            if ($path !== null && $path !== '' && ($host === null || $host === $request->getHost())) {
                $destino = $path;
            }
        }

        return redirect()->to(
            $destino !== null && $user->canReach($destino) ? $destino : $user->homeUrl(),
        );
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
