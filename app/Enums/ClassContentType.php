<?php

namespace App\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Tipos de contenido de una clase. Corresponde al ENUM de `class_content.type`
 * definido en docs/PLAN_ARQUITECTONICO.md §2.
 */
enum ClassContentType: string implements HasColor, HasIcon, HasLabel
{
    case Video = 'video';
    case Pdf = 'pdf';
    case Text = 'text';
    case Task = 'task';

    public function getLabel(): string
    {
        return match ($this) {
            self::Video => 'Video',
            self::Pdf => 'PDF',
            self::Text => 'Texto',
            self::Task => 'Tarea',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Video => 'danger',
            self::Pdf => 'warning',
            self::Text => 'gray',
            self::Task => 'primary',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Video => 'heroicon-o-play-circle',
            self::Pdf => 'heroicon-o-document-text',
            self::Text => 'heroicon-o-bars-3-bottom-left',
            self::Task => 'heroicon-o-clipboard-document-check',
        };
    }

    /** Los que guardan un archivo o enlace en `content_url`. */
    public function hasUrl(): bool
    {
        return in_array($this, [self::Video, self::Pdf], true);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
