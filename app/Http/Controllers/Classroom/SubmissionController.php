<?php

namespace App\Http\Controllers\Classroom;

use App\Models\ClassContent;
use App\Services\ProgressService;
use App\Services\SubmissionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Exceptions\SubmissionException;
use App\Http\Requests\Classroom\SubmitTaskRequest;

/** La entrega de un trabajo práctico. */
class SubmissionController extends Controller
{
    public function store(
        SubmitTaskRequest $request,
        ClassContent $content,
        SubmissionService $entregas,
        ProgressService $progreso,
    ): RedirectResponse {
        $student = $request->user()->student;

        // No alcanza con conocer el id del contenido: la clase tiene que estar
        // abierta para este alumno, igual que para verla
        $motivo = $progreso->lockReason($student, $content->class);

        abort_if($motivo !== null, 403, $motivo);

        try {
            $entregas->submit($student, $content, $request->file('archivo'));
        } catch (SubmissionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('exito', "Entregaste «{$content->title}». Te avisamos cuando esté corregida.");
    }
}
