<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Certificate;
use App\Models\CourseEnrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Verificación pública de un certificado, sin sesión.
 *
 * Es para quien recibe un certificado y no tiene cuenta acá —un empleador,
 * otra institución—: /curso/{course} y / son las otras dos excepciones a
 * «nada sin sesión» (ver PublicShowcaseTest), y ésta es la tercera.
 */
class CertificateVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function certificadoReal(): Certificate
    {
        $enrollment = CourseEnrollment::factory()->completed()->create();

        return Certificate::factory()->for($enrollment, 'enrollment')->create([
            'certificate_number' => 'AAMEVI-2026-TEST01',
        ]);
    }

    public function test_sin_sesion_puede_verificar_un_numero_real(): void
    {
        $certificado = $this->certificadoReal();

        $response = $this->get('/verificar-certificado?numero=AAMEVI-2026-TEST01');

        $response->assertOk();
        $response->assertSee('Este certificado es real');
        $response->assertSee($certificado->enrollment->student->user->full_name);
        $response->assertSee($certificado->enrollment->course->title);
    }

    public function test_un_numero_inventado_no_encuentra_nada(): void
    {
        $response = $this->get('/verificar-certificado?numero=AAMEVI-2026-INVENTADO');

        $response->assertOk();
        $response->assertSee('No encontramos ningún certificado');
    }

    /** Sin buscar todavía, no hay que mostrar ni éxito ni error. */
    public function test_sin_numero_no_muestra_ningun_resultado(): void
    {
        $response = $this->get('/verificar-certificado');

        $response->assertOk();
        $response->assertDontSee('Este certificado es real');
        $response->assertDontSee('No encontramos ningún certificado');
    }

    /** No es una ficha del alumno: sin email ni DNI. */
    public function test_no_muestra_datos_de_contacto_del_alumno(): void
    {
        $enrollment = CourseEnrollment::factory()->completed()->create();
        $student = $enrollment->student;
        $student->update(['dni' => '30111222']);

        Certificate::factory()->for($enrollment, 'enrollment')->create([
            'certificate_number' => 'AAMEVI-2026-TEST02',
        ]);

        $response = $this->get('/verificar-certificado?numero=AAMEVI-2026-TEST02');

        $response->assertDontSee($student->user->email);
        $response->assertDontSee('30111222');
    }
}
