<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Los avisos que manda la plataforma (docs/PLAN_ARQUITECTONICO.md §2).
 *
 * El tipo se guarda además del asunto porque el asunto es texto para el
 * destinatario y esto es para nosotros: permite filtrar la cola, contar cuántos
 * recordatorios salieron, o reintentar sólo una clase de aviso cuando falló el
 * proveedor durante una hora.
 */
enum EmailType: string implements HasColor, HasLabel
{
    case Verification = 'verification';
    case EnrollmentApproved = 'enrollment_approved';
    case ClassReminder = 'class_reminder';
    case Certificate = 'certificate';
    case TaskGraded = 'task_graded';
    case Announcement = 'announcement';
    case SupportReply = 'support_reply';

    public function getLabel(): string
    {
        return match ($this) {
            self::Verification => 'Verificación de cuenta',
            self::EnrollmentApproved => 'Inscripción aprobada',
            self::ClassReminder => 'Recordatorio de clase',
            self::Certificate => 'Certificado emitido',
            self::TaskGraded => 'Trabajo corregido',
            self::Announcement => 'Comunicación del curso',
            self::SupportReply => 'Respuesta a una consulta',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Verification => 'gray',
            self::EnrollmentApproved => 'success',
            self::ClassReminder => 'warning',
            self::Certificate => 'primary',
            self::TaskGraded => 'info',
            self::Announcement => 'primary',
            self::SupportReply => 'info',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
