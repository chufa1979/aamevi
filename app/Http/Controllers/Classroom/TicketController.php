<?php

namespace App\Http\Controllers\Classroom;

use App\Models\Course;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Models\SupportTicket;
use App\Services\SupportService;
use App\Exceptions\SupportException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Classroom\StoreTicketRequest;

/**
 * Las consultas del alumno.
 *
 * El listado es de todas sus consultas, de todos sus cursos: la pregunta que se
 * hace uno es «¿qué pregunté y qué me contestaron?», no «¿qué pregunté en este
 * curso?». El curso se elige al abrirla y se muestra en cada fila.
 */
class TicketController extends Controller
{
    public function index(Request $request, SupportService $consultas): View
    {
        $student = $request->user()->student;

        return view('classroom.tickets', [
            'consultas' => $consultas->forStudent($student),
            'cursos' => $student->enrollments()
                ->with('course')
                ->get()
                ->filter(fn ($e): bool => $e->status->ocupaCupo() && $e->course !== null)
                ->map(fn ($e): Course => $e->course)
                ->sortBy('title')
                ->values(),
        ]);
    }

    public function store(StoreTicketRequest $request, SupportService $consultas): RedirectResponse
    {
        $course = Course::findOrFail($request->string('course_id')->value());

        try {
            $ticket = $consultas->open(
                $request->user()->student,
                $course,
                $request->string('subject')->value(),
                $request->string('message')->value(),
            );
        } catch (SupportException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()
            ->route('classroom.ticket', $ticket)
            ->with('exito', 'Enviamos tu consulta. Te avisamos por correo cuando te respondan.');
    }

    public function show(Request $request, SupportTicket $ticket, SupportService $consultas): View
    {
        $this->suya($request, $ticket);

        $consultas->markRead($ticket);

        return view('classroom.ticket', [
            'ticket' => $ticket->load(['messages.author', 'course']),
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket, SupportService $consultas): RedirectResponse
    {
        $this->suya($request, $ticket);

        $request->validate(
            ['message' => ['required', 'string', 'max:5000']],
            ['message.required' => 'Escribí tu mensaje.'],
        );

        try {
            $consultas->reply($ticket, $request->user(), $request->string('message')->value());
        } catch (SupportException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('exito', 'Mensaje enviado.');
    }

    public function close(Request $request, SupportTicket $ticket, SupportService $consultas): RedirectResponse
    {
        $this->suya($request, $ticket);

        $consultas->close($ticket);

        return back()->with('exito', 'Consulta cerrada.');
    }

    /** Las consultas de otro no se abren, ni con el enlace en la mano. */
    private function suya(Request $request, SupportTicket $ticket): void
    {
        abort_unless($ticket->student_id === $request->user()->student->getKey(), 404);
    }
}
