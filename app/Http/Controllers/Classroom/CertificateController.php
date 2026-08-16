<?php

namespace App\Http\Controllers\Classroom;

use Illuminate\View\View;
use App\Models\Certificate;
use Illuminate\Http\Request;
use App\Models\CourseEnrollment;
use App\Http\Controllers\Controller;
use App\Services\CertificateService;
use Symfony\Component\HttpFoundation\Response;

/** Los certificados del alumno: los que tiene y los que le faltan. */
class CertificateController extends Controller
{
    public function index(Request $request, CertificateService $certificados): View
    {
        $student = $request->user()->student;

        $emitidos = $certificados->forStudent($student);
        $conCertificado = $emitidos->pluck('enrollment.course_id');

        /*
         * Los cursos que está haciendo y todavía no le dieron certificado, con el
         * motivo. Sin esto la pantalla estaría vacía hasta que termine el primer
         * curso, y no diría qué le falta para llegar.
         */
        $enCurso = $student->enrollments()
            ->with('course')
            ->get()
            ->filter(fn (CourseEnrollment $e): bool => $e->status->ocupaCupo()
                && $e->course !== null
                && ! $conCertificado->contains($e->course_id))
            ->map(fn (CourseEnrollment $e): array => [
                'course' => $e->course,
                'falta' => $certificados->blocker($student, $e->course),
            ])
            ->values();

        return view('classroom.certificates', [
            'certificados' => $emitidos,
            'enCurso' => $enCurso,
        ]);
    }

    /**
     * Baja el PDF.
     *
     * El certificado se resuelve acotado al alumno, no por su id a secas: son
     * UUID y no se adivinan, pero el que tiene el link de un compañero no tiene
     * por qué poder bajarlo.
     */
    public function download(Request $request, Certificate $certificate, CertificateService $certificados): Response
    {
        $student = $request->user()->student;

        abort_unless($certificate->enrollment?->student_id === $student->getKey(), 404);

        return $certificados->pdf($certificate)->download($certificados->fileName($certificate));
    }
}
