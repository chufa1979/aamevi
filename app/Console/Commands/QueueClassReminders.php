<?php

namespace App\Console\Commands;

use App\Enums\EmailType;
use App\Models\CourseClass;
use App\Enums\EnrollmentStatus;
use Illuminate\Console\Command;
use App\Models\CourseEnrollment;
use App\Services\NotificationService;

/**
 * Encola el recordatorio de las clases en vivo que se dictan mañana.
 *
 * Sólo las **en vivo**: una clase grabada se ve cuando el alumno puede, y
 * avisarle de cada una sería mandarle un correo por clase del cronograma. La que
 * tiene hora de encuentro es la única donde llegar tarde cuesta algo.
 *
 * Corre una vez por día. La ventana es de un día entero a partir del momento del
 * aviso, así que dos corridas del mismo día ven las mismas clases: por eso se
 * saltean las que ya tienen aviso encolado para ese alumno.
 */
class QueueClassReminders extends Command
{
    protected $signature = 'emails:recordatorios';

    protected $description = 'Encola los recordatorios de las clases en vivo de mañana';

    public function handle(NotificationService $avisos): int
    {
        $desde = now();
        $hasta = now()->addHours(NotificationService::HORAS_DE_AVISO);

        $clases = CourseClass::query()
            ->where('is_live_session', true)
            ->whereBetween('activation_date', [$desde, $hasta])
            ->with('module.course')
            ->get();

        $encolados = 0;

        foreach ($clases as $class) {
            $course = $class->module?->course;

            if ($course === null) {
                continue;
            }

            $inscripciones = CourseEnrollment::query()
                ->where('course_id', $course->getKey())
                ->whereIn('status', EnrollmentStatus::ocupantes())
                ->with('student.user')
                ->get();

            foreach ($inscripciones as $enrollment) {
                $student = $enrollment->student;

                if ($student?->user === null) {
                    continue;
                }

                if ($avisos->alreadyQueued($student->user, EmailType::ClassReminder, $class->title)) {
                    continue;
                }

                if ($avisos->classReminder($class, $student) !== null) {
                    $encolados++;
                }
            }
        }

        $this->info("Recordatorios encolados: {$encolados} para {$clases->count()} clases en vivo.");

        return self::SUCCESS;
    }
}
