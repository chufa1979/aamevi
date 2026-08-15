<?php

namespace App\Exceptions;

use DomainException;
use App\Models\Course;
use App\Enums\EnrollmentStatus;

/**
 * Reglas de negocio de las inscripciones que no se pueden violar.
 *
 * Son excepciones y no `return false` a propósito: si algo intenta aprobar una
 * inscripción ya rechazada, es un error de programación y tiene que romper
 * ruidosamente, no seguir de largo.
 */
class EnrollmentException extends DomainException
{
    public static function invalidTransition(EnrollmentStatus $from, string $accion): self
    {
        return new self(
            "No se puede {$accion} una inscripción en estado «{$from->getLabel()}»."
        );
    }

    public static function courseIsFull(Course $course): self
    {
        return new self(
            "El curso «{$course->title}» alcanzó su cupo de {$course->max_students} alumnos."
        );
    }

    public static function alreadyEnrolled(Course $course): self
    {
        return new self(
            "Ya tenés una solicitud o una inscripción en «{$course->title}»."
        );
    }

    public static function courseIsClosed(Course $course): self
    {
        return new self(
            "El curso «{$course->title}» no está abierto a inscripciones."
        );
    }
}
