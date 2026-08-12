<?php

namespace App\Exceptions;

use DomainException;

class QuizException extends DomainException
{
    public static function invalidOwner(): self
    {
        return new self(
            'Una evaluación tiene que pertenecer a una clase o a un módulo, no a ambos ni a ninguno.'
        );
    }

    public static function notReady(): self
    {
        return new self('Esta evaluación todavía no tiene preguntas cargadas.');
    }

    public static function noAttemptsLeft(int $max): self
    {
        return new self("Ya usaste los {$max} intentos permitidos.");
    }

    public static function alreadySubmitted(): self
    {
        return new self('Este intento ya fue entregado.');
    }

    public static function questionNotAssigned(): self
    {
        return new self('Se respondió una pregunta que no formaba parte del intento.');
    }
}
