<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Student;
use App\Models\Announcement;
use App\Enums\EnrollmentStatus;
use App\Models\AnnouncementRead;
use Illuminate\Database\Eloquent\Collection;

/**
 * El tablón de comunicaciones del curso.
 *
 * Publicar y avisar son dos cosas: la comunicación queda siempre en el tablón, y
 * el correo sale sólo si quien la escribe lo pide. Un aviso de que se corrió una
 * clase merece correo; una nota de color, no. Obligar a que todo salga por mail
 * convierte el tablón en una lista de difusión y termina desalentando al docente
 * de publicar lo menor.
 */
class AnnouncementService
{
    public function __construct(private readonly NotificationService $avisos) {}

    /**
     * Manda la comunicación por correo a quien corresponda.
     *
     * Idempotente: `notified_at` deja registrado que ya salió, así que editar el
     * texto después no vuelve a llenar la casilla de nadie.
     *
     * @return int a cuántos se les encoló
     */
    public function notify(Announcement $announcement): int
    {
        if ($announcement->wasNotified()) {
            return 0;
        }

        $destinatarios = $this->destinatarios($announcement);

        foreach ($destinatarios as $student) {
            $this->avisos->announcement($announcement, $student);
        }

        $announcement->update(['notified_at' => now()]);

        return $destinatarios->count();
    }

    /**
     * Las comunicaciones que le tocan a este alumno en un curso.
     *
     * @return Collection<int, Announcement>
     */
    public function forStudent(Student $student, Course $course): Collection
    {
        return Announcement::query()
            ->where('course_id', $course->getKey())
            ->visibleToStudent($student)
            ->with('author')
            ->orderByDesc('published_at')
            ->get();
    }

    /**
     * Cuántas comunicaciones sin leer tiene, en un curso o en todos.
     *
     * Es el número del menú del aula. Sin él, entrar al tablón es un acto de fe:
     * el alumno no tiene forma de saber si hay algo nuevo salvo mirando.
     */
    public function unreadFor(Student $student, ?Course $course = null): int
    {
        return Announcement::query()
            ->visibleToStudent($student)
            ->when($course !== null, fn ($q) => $q->where('course_id', $course->getKey()))
            // Sólo de los cursos que cursa: una comunicación de un curso del que
            // se dio de baja no es un pendiente suyo
            ->whereIn('course_id', $student->enrollments()
                ->whereIn('status', EnrollmentStatus::ocupantes())
                ->select('course_id'))
            ->whereNotExists(fn ($q) => $q
                ->selectRaw('1')
                ->from('announcement_reads')
                ->whereColumn('announcement_reads.announcement_id', 'announcements.id')
                ->where('announcement_reads.student_id', $student->getKey()))
            ->count();
    }

    /**
     * Da por leídas las comunicaciones que el alumno tiene delante.
     *
     * El tablón muestra el texto completo de cada una, así que abrirlo **es**
     * leerlas: pedirle además un clic por comunicación sería inventar trabajo
     * para sostener un contador.
     *
     * @param  Collection<int, Announcement>  $comunicaciones
     */
    public function markRead(Student $student, Collection $comunicaciones): void
    {
        foreach ($comunicaciones as $comunicacion) {
            AnnouncementRead::firstOrCreate(
                ['announcement_id' => $comunicacion->getKey(), 'student_id' => $student->getKey()],
                ['read_at' => now()],
            );
        }
    }

    /**
     * A quién va: al alumno indicado, o a todos los que cursan.
     *
     * @return Collection<int, Student>
     */
    private function destinatarios(Announcement $announcement): Collection
    {
        if (! $announcement->isForEveryone()) {
            return Student::query()->whereKey($announcement->student_id)->with('user')->get();
        }

        return Student::query()
            ->whereIn('id', $announcement->course->enrollments()
                ->whereIn('status', EnrollmentStatus::ocupantes())
                ->select('student_id'))
            ->with('user')
            ->get();
    }
}
