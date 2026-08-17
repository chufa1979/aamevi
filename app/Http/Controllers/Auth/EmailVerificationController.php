<?php

namespace App\Http\Controllers\Auth;

use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

/**
 * Verificación del correo.
 *
 * El enlace que se manda va firmado y con vencimiento: lo arma Laravel a partir
 * del id del usuario y del hash de su correo, así que cambiar la dirección
 * invalida los enlaces viejos.
 */
class EmailVerificationController extends Controller
{
    /** La pantalla de «revisá tu correo». */
    public function notice(Request $request): View|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('home')
            : view('auth.verify-email');
    }

    /**
     * El enlace del correo.
     *
     * `EmailVerificationRequest` valida la firma y que el hash corresponda al
     * usuario con sesión iniciada; si no, aborta antes de llegar acá.
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('classroom.catalog');
        }

        $request->fulfill();

        return redirect()
            ->route('classroom.catalog')
            ->with('exito', 'Listo, tu correo quedó verificado. Ya podés inscribirte a un curso.');
    }

    /** Reenvía el aviso. La ruta está limitada para que no se use de ametralladora. */
    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('exito', 'Te reenviamos el correo de verificación.');
    }
}
