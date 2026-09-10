<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Modalidad de dictado de un curso, para la ficha pública y el CRUD del panel. */
enum CourseModality: string implements HasColor, HasLabel
{
    case Online = 'online';
    case Presencial = 'presencial';
    case Semipresencial = 'semipresencial';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Presencial => 'Presencial',
            self::Semipresencial => 'Semipresencial',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Online => 'primary',
            self::Presencial => 'warning',
            self::Semipresencial => 'gray',
        };
    }
}
