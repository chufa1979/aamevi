<?php

namespace App\Exceptions;

use DomainException;
use App\Models\ClassContent;

/**
 * Reglas de las entregas de tareas.
 *
 * Como el resto del dominio, son excepciones y no `return false`: intentar
 * entregar dos veces o corregir algo ya corregido es un error de programación y
 * tiene que romper ruidosamente. La pantalla las atrapa y las convierte en un
 * mensaje.
 */
class SubmissionException extends DomainException
{
    public static function notATask(ClassContent $content): self
    {
        return new self(
            "El contenido «{$content->title}» no es una tarea: no se le puede entregar nada."
        );
    }

    public static function alreadySubmitted(): self
    {
        return new self(
            'Ya entregaste esta tarea. Vas a poder volver a entregar sólo si te la desaprueban.'
        );
    }

    public static function pastDue(ClassContent $content): self
    {
        return new self(
            'La fecha de entrega venció el '.$content->due_date->format('d/m/Y').'.'
        );
    }

    public static function alreadyGraded(): self
    {
        return new self('Esta entrega ya fue corregida.');
    }

    public static function notGraded(): self
    {
        return new self('No se puede publicar una entrega que todavía no fue corregida.');
    }

    public static function invalidGrade(float $nota): self
    {
        return new self("La nota {$nota} está fuera de la escala de 1 a 10.");
    }
}
