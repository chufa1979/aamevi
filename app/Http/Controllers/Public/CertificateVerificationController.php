<?php

namespace App\Http\Controllers\Public;

use Illuminate\View\View;
use App\Models\Certificate;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

/**
 * Confirmar que un número de certificado es real, sin sesión.
 *
 * Pensada para quien recibe un certificado de un alumno —un empleador, otra
 * institución— y no tiene por qué tener cuenta acá. El link vive impreso en
 * el propio PDF (ver certificates/pdf.blade.php), no en el menú del sitio.
 *
 * Muestra lo mínimo para confirmar legitimidad: quién, qué curso, cuándo. Ni
 * email ni DNI — esto no es una ficha del alumno.
 */
class CertificateVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $numero = $request->string('numero')->trim()->toString();

        $certificado = $numero === '' ? null : Certificate::query()
            ->where('certificate_number', $numero)
            ->with(['enrollment.student.user', 'enrollment.course'])
            ->first();

        return view('public.certificate-verify', [
            'numero' => $numero,
            'certificado' => $certificado,
            'buscado' => $numero !== '',
        ]);
    }
}
