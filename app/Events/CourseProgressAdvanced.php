<?php

namespace App\Events;

use App\Models\Course;
use App\Models\Student;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * El alumno avanzó en el curso.
 *
 * Existe para no repartir por las pantallas la pregunta «¿y ahora terminó?».
 * Hay dos momentos en que un curso puede pasar a estar terminado —completar una
 * clase y publicar la corrección de la última tarea— y son tres los lugares que
 * los provocan: el aula, la entrega de una evaluación y el panel. Con una
 * llamada directa, el cuarto lugar que aparezca se va a olvidar de hacerla.
 *
 * Las notificaciones de la fase 5 van a engancharse acá mismo.
 */
class CourseProgressAdvanced
{
    use Dispatchable;

    public function __construct(
        public readonly Student $student,
        public readonly Course $course,
    ) {}
}
