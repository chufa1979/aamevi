<?php

namespace App\Services;

use App\Models\User;
use App\Models\Course;
use App\Models\Student;
use App\Enums\TicketStatus;
use App\Models\SupportTicket;
use App\Models\SupportMessage;
use App\Enums\EnrollmentStatus;
use Illuminate\Support\Facades\DB;
use App\Exceptions\SupportException;
use Illuminate\Database\Eloquent\Collection;

/**
 * El ciclo de una consulta a mesa de ayuda.
 *
 * El estado no se asigna: sale de quién escribió el último mensaje. Si escribe
 * el alumno queda esperando respuesta; si contesta el docente, respondida. Por
 * eso las dos operaciones son métodos y no un `update(['status' => …])` suelto
 * repartido por las pantallas.
 *
 * Una consulta cerrada no recibe mensajes. Reabrirla con una repregunta dejaría
 * hilos eternos donde la última respuesta queda enterrada; abrir una nueva es
 * más barato de leer para quien la atiende.
 */
class SupportService
{
    public function __construct(private readonly NotificationService $avisos) {}

    /**
     * Abre una consulta con su primer mensaje.
     *
     * @throws SupportException si el alumno no cursa ese curso o el texto está vacío
     */
    public function open(Student $student, Course $course, string $asunto, string $mensaje): SupportTicket
    {
        if (! $this->cursa($student, $course)) {
            throw SupportException::notEnrolled($course);
        }

        $this->exigirTexto($mensaje);

        return DB::transaction(function () use ($student, $course, $asunto, $mensaje): SupportTicket {
            $ticket = SupportTicket::create([
                'course_id' => $course->getKey(),
                'student_id' => $student->getKey(),
                'subject' => trim($asunto),
                'status' => TicketStatus::Open,
            ]);

            $this->escribir($ticket, $student->user, $mensaje);

            return $ticket;
        });
    }

    /**
     * Suma un mensaje al hilo y mueve el estado según quién escribió.
     *
     * @throws SupportException si la consulta está cerrada o el texto está vacío
     */
    public function reply(SupportTicket $ticket, User $autor, string $mensaje): SupportMessage
    {
        if ($ticket->isClosed()) {
            throw SupportException::closed();
        }

        $this->exigirTexto($mensaje);

        $esDelAlumno = $autor->getKey() === $ticket->student_id;

        return DB::transaction(function () use ($ticket, $autor, $mensaje, $esDelAlumno): SupportMessage {
            $message = $this->escribir($ticket, $autor, $mensaje);

            $ticket->update([
                'status' => $esDelAlumno ? TicketStatus::Open : TicketStatus::Answered,
            ]);

            // Al alumno se le avisa; el docente ve el pendiente en su panel, y un
            // correo por cada repregunta lo llenaría de ruido
            if (! $esDelAlumno) {
                $this->avisos->supportReplied($ticket->fresh(), $message);
            }

            return $message;
        });
    }

    public function close(SupportTicket $ticket): SupportTicket
    {
        $ticket->update([
            'status' => TicketStatus::Closed,
            'closed_at' => now(),
        ]);

        return $ticket->fresh();
    }

    /**
     * Las consultas de un alumno, la más movida primero.
     *
     * @return Collection<int, SupportTicket>
     */
    public function forStudent(Student $student): Collection
    {
        return SupportTicket::query()
            ->where('student_id', $student->getKey())
            ->with('course')
            ->withCount('messages')
            ->orderByDesc('updated_at')
            ->get();
    }

    /** ¿Este usuario puede leer y contestar esta consulta? */
    public function canHandle(User $user, SupportTicket $ticket): bool
    {
        return $user->isAdmin()
            || ($user->isTeacher() && $ticket->course?->isTaughtBy($user));
    }

    private function escribir(SupportTicket $ticket, ?User $autor, string $mensaje): SupportMessage
    {
        // `touch` para que el orden por actividad tenga sentido: sin esto, una
        // consulta con respuesta de hoy queda ordenada por su fecha de apertura
        $ticket->touch();

        return SupportMessage::create([
            'ticket_id' => $ticket->getKey(),
            'author_id' => $autor?->getKey(),
            'body' => trim($mensaje),
        ]);
    }

    private function cursa(Student $student, Course $course): bool
    {
        return $course->enrollments()
            ->where('student_id', $student->getKey())
            ->whereIn('status', EnrollmentStatus::ocupantes())
            ->exists();
    }

    /** @throws SupportException */
    private function exigirTexto(string $mensaje): void
    {
        if (trim($mensaje) === '') {
            throw SupportException::emptyMessage();
        }
    }
}
