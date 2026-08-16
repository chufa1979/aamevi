<?php

namespace App\Services;

use Throwable;
use App\Models\User;
use App\Models\Student;
use App\Enums\EmailType;
use App\Enums\EmailStatus;
use App\Models\Certificate;
use App\Models\CourseClass;
use App\Models\QueuedEmail;
use Carbon\CarbonInterface;
use App\Models\TaskSubmission;
use App\Models\CourseEnrollment;
use Illuminate\Support\Facades\Mail;

/**
 * Los avisos de la plataforma: qué se manda, y cómo sale.
 *
 * Nada se envía en el momento. Todo se escribe en `email_queue` y un comando la
 * vacía —`emails:enviar`, que en el servidor corre por cron—. La razón es el
 * hosting: es compartido, no hay forma de dejar un proceso corriendo, y mandar
 * en línea ataría el tiempo de respuesta de una pantalla a que conteste un
 * servidor de correo ajeno. Aprobar una inscripción no puede tardar ocho
 * segundos porque el SMTP está lento.
 *
 * El asunto y el cuerpo se arman acá, al encolar, y quedan guardados: lo que
 * figura en la tabla es exactamente lo que salió.
 */
class NotificationService
{
    /** Cuántas veces se reintenta antes de darlo por perdido. */
    public const MAX_INTENTOS = 3;

    /** Con cuánta anticipación se avisa una clase en vivo. */
    public const HORAS_DE_AVISO = 24;

    // ── Qué se avisa ────────────────────────────────────────────────────────

    /**
     * Verificación del correo, con el enlace firmado que arma Laravel.
     *
     * El enlace se calcula al encolar y no al enviar: si el worker demora, el
     * vencimiento corre desde que el alumno apretó «crear cuenta», que es lo que
     * él percibe como el momento del pedido.
     */
    public function verification(User $user, string $enlace): QueuedEmail
    {
        return $this->encolar(
            $user,
            EmailType::Verification,
            'Verificá tu correo en AAMEVi',
            'emails.verification',
            ['user' => $user, 'enlace' => $enlace],
        );
    }

    public function enrollmentApproved(CourseEnrollment $enrollment): ?QueuedEmail
    {
        $user = $enrollment->student?->user;
        $course = $enrollment->course;

        if ($user === null || $course === null) {
            return null;
        }

        return $this->encolar(
            $user,
            EmailType::EnrollmentApproved,
            "Ya podés empezar «{$course->title}»",
            'emails.enrollment-approved',
            ['user' => $user, 'course' => $course],
        );
    }

    public function taskGraded(TaskSubmission $submission): ?QueuedEmail
    {
        $user = $submission->student?->user;
        $content = $submission->content;

        if ($user === null || $content === null || ! $submission->isVisibleToStudent()) {
            return null;
        }

        return $this->encolar(
            $user,
            EmailType::TaskGraded,
            "Corrigieron «{$content->title}»",
            'emails.task-graded',
            ['user' => $user, 'submission' => $submission, 'content' => $content],
        );
    }

    public function certificateIssued(Certificate $certificate): ?QueuedEmail
    {
        $user = $certificate->enrollment?->student?->user;
        $course = $certificate->enrollment?->course;

        if ($user === null || $course === null) {
            return null;
        }

        return $this->encolar(
            $user,
            EmailType::Certificate,
            "Tu certificado de «{$course->title}»",
            'emails.certificate',
            ['user' => $user, 'course' => $course, 'certificate' => $certificate],
        );
    }

    /**
     * Recordatorio de una clase en vivo.
     *
     * Se programa para `HORAS_DE_AVISO` antes de la clase, no para ahora: por
     * eso `scheduled_at` está separado de `created_at`. Si esa hora ya pasó
     * —porque la clase se cargó sobre la fecha— sale igual, cuanto antes.
     */
    public function classReminder(CourseClass $class, Student $student): ?QueuedEmail
    {
        $user = $student->user;

        if ($user === null || $class->activation_date === null) {
            return null;
        }

        $cuando = $class->activation_date->copy()->subHours(self::HORAS_DE_AVISO);

        return $this->encolar(
            $user,
            EmailType::ClassReminder,
            "Mañana: «{$class->title}»",
            'emails.class-reminder',
            ['user' => $user, 'class' => $class, 'course' => $class->module?->course],
            $cuando->isPast() ? now() : $cuando,
        );
    }

    /** ¿Ya se le encoló este aviso? Evita duplicar recordatorios en cada corrida. */
    public function alreadyQueued(User $user, EmailType $type, string $enElAsunto): bool
    {
        return QueuedEmail::where('recipient_id', $user->getKey())
            ->where('email_type', $type)
            ->where('subject', 'like', '%'.$enElAsunto.'%')
            ->exists();
    }

    // ── Cómo sale ───────────────────────────────────────────────────────────

    /**
     * Manda los avisos cuya hora llegó.
     *
     * @return array{enviados: int, fallidos: int}
     */
    public function drain(int $limite = 50): array
    {
        $enviados = 0;
        $fallidos = 0;

        foreach (QueuedEmail::query()->due()->with('recipient')->limit($limite)->get() as $email) {
            $this->send($email) ? $enviados++ : $fallidos++;
        }

        return ['enviados' => $enviados, 'fallidos' => $fallidos];
    }

    /**
     * Manda uno.
     *
     * Devuelve false en lugar de propagar: un aviso que no sale no puede cortar
     * la tanda, y el motivo queda en `last_error`. Es la excepción a la regla de
     * que el dominio lanza — acá el error es del mundo exterior, no un programa
     * mal escrito.
     */
    public function send(QueuedEmail $email): bool
    {
        $destinatario = $email->recipient;

        if ($destinatario === null) {
            $this->fallar($email, 'El destinatario ya no existe.');

            return false;
        }

        try {
            Mail::html($email->body, function ($mensaje) use ($email, $destinatario): void {
                $mensaje->to($destinatario->email, $destinatario->full_name)
                    ->subject($email->subject);
            });
        } catch (Throwable $e) {
            $this->fallar($email, $e->getMessage());

            return false;
        }

        $email->update([
            'status' => EmailStatus::Sent,
            'sent_at' => now(),
            'last_error' => null,
        ]);

        return true;
    }

    /** Vuelve a poner en cola uno que se dio por perdido. Lo usa el panel. */
    public function retry(QueuedEmail $email): QueuedEmail
    {
        $email->update([
            'status' => EmailStatus::Pending,
            'retry_count' => 0,
            'scheduled_at' => now(),
            'last_error' => null,
        ]);

        return $email->fresh();
    }

    /**
     * Anota el intento fallido.
     *
     * Mientras queden intentos sigue pendiente, con la próxima salida cada vez
     * más lejos: si el problema es que el proveedor está caído, insistir cada un
     * minuto no ayuda. Agotados los intentos pasa a `failed` y ahí se queda
     * hasta que alguien lo reintente a mano desde el panel.
     */
    private function fallar(QueuedEmail $email, string $motivo): void
    {
        $intentos = $email->retry_count + 1;

        $email->update([
            'retry_count' => $intentos,
            'last_error' => $motivo,
            'status' => $intentos >= self::MAX_INTENTOS ? EmailStatus::Failed : EmailStatus::Pending,
            'scheduled_at' => $intentos >= self::MAX_INTENTOS
                ? $email->scheduled_at
                : now()->addMinutes(5 * $intentos),
        ]);
    }

    /** @param array<string, mixed> $datos */
    private function encolar(
        User $user,
        EmailType $tipo,
        string $asunto,
        string $vista,
        array $datos,
        ?CarbonInterface $cuando = null,
    ): QueuedEmail {
        return QueuedEmail::create([
            'recipient_id' => $user->getKey(),
            'email_type' => $tipo,
            'subject' => $asunto,
            'body' => view($vista, [...$datos, 'asunto' => $asunto])->render(),
            'scheduled_at' => $cuando ?? now(),
            'status' => EmailStatus::Pending,
        ]);
    }
}
