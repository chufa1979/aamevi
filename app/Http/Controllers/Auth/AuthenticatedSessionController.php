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

        /*
         * `intended` primero: si la persona venía de una URL concreta —el enlace
         * a una clase, por ejemplo— y le pedimos la contraseña en el camino, hay
         * que devolverla ahí. Recién si no venía de ningún lado la mandamos a lo
         * suyo, que depende del rol.
         */
        return redirect()->intended($request->user()->homeUrl());
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
