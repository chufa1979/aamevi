<?php

namespace App\Exceptions;

use DomainException;
use App\Models\Course;
use App\Models\CourseEnrollment;

/**
 * Reglas de la emisión de certificados.
 *
 * Como el resto del dominio, son excepciones y no `return false`: emitir dos
 * veces el mismo certificado o emitirle uno a quien no cursó es un error de
 * programación, no una condición esperable. La emisión automática, que sí es
 * condición esperable, no pasa por acá — `issueIfEarned()` devuelve null.
 */
class CertificateException extends DomainException
{
    public static function alreadyIssued(CourseEnrollment $enrollment): self
    {
        return new self(
            "El alumno ya tiene certificado del curso «{$enrollment->course->title}»."
        );
    }

    public static function notEnrolled(Course $course): self
    {
        return new self(
            "El alumno no está inscripto en «{$course->title}»: no se le puede emitir el certificado."
        );
    }
}
