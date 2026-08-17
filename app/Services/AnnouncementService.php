<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Student;
use App\Models\Announcement;
use App\Enums\EnrollmentStatus;
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
     * Cuántas hay para este alumno.
     *
     * Cuántas tiene **sin leer** sería más útil, pero registrar la lectura pide
     * una tabla más para un módulo que en FID se usó una vez en años.
     */
    public function countForStudent(Student $student, Course $course): int
    {
        return Announcement::query()
            ->where('course_id', $course->getKey())
            ->visibleToStudent($student)
            ->count();
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
