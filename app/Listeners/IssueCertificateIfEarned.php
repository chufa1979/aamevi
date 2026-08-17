<?php

namespace App\Listeners;

use App\Services\CertificateService;
use App\Events\CourseProgressAdvanced;

/**
 * Emite el certificado cuando el alumno termina el curso.
 *
 * Va como listener y no dentro de `ProgressService` porque el certificado
 * necesita preguntarle a ese servicio si el curso está completo: llamarlo desde
 * adentro sería un círculo. El evento corta la dependencia en un solo sentido.
 *
 * `issueIfEarned()` es idempotente y devuelve null si todavía no corresponde, así
 * que no importa cuántas veces se dispare el evento.
 */
class IssueCertificateIfEarned
{
    public function __construct(private readonly CertificateService $certificates) {}

    public function handle(CourseProgressAdvanced $event): void
    {
        $this->certificates->issueIfEarned($event->student, $event->course);
    }
}
