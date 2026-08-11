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
}
