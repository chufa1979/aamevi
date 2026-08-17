<?php

namespace App\Exceptions;

use DomainException;
use App\Models\Course;

/**
 * Reglas de las consultas a mesa de ayuda.
 *
 * Como el resto del dominio, son excepciones: escribir en una consulta cerrada o
 * abrir una en un curso que no se cursa son errores de programación, no
 * condiciones que la pantalla tenga que contemplar.
 */
class SupportException extends DomainException
{
    public static function notEnrolled(Course $course): self
    {
        return new self(
            "No se puede abrir una consulta de «{$course->title}»: el alumno no está cursando."
        );
    }

    public static function closed(): self
    {
        return new self('La consulta está cerrada. Para seguir, abrí una nueva.');
    }

    public static function emptyMessage(): self
    {
        return new self('El mensaje está vacío.');
    }
}
