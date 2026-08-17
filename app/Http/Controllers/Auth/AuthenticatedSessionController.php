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
