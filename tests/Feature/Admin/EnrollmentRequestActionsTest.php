<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use Livewire\Livewire;
use App\Enums\EmailType;
use App\Models\QueuedEmail;
use App\Models\EnrollmentNote;
use Filament\Facades\Filament;
use App\Models\CourseEnrollment;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\EnrollmentRequests\Pages\ManageEnrollmentRequests;

/**
 * Lo que se agregó a Solicitudes para que sea la pantalla de trabajo completa
 * de quien procesa inscripciones: ver al alumno sin salir de acá, dejar
 * constancia de una gestión y avisarle algo administrativo.
 */
class EnrollmentRequestActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());

        Filament::setCurrentPanel('admin');
    }

    private function tabla()
    {
        return Livewire::test(ManageEnrollmentRequests::class);
    }

    public function test_ver_alumno_muestra_su_ficha(): void
    {
        $enrollment = CourseEnrollment::factory()->create();
        $enrollment->student->update(['dni' => '30111222', 'university' => 'Universidad de Buenos Aires']);

        $modal = $this->tabla()->mountAction(TestAction::make('ver_alumno')->table($enrollment));

        $html = (string) $modal->instance()->getMountedAction()->getModalContent()->render();

        $this->assertStringContainsString('30111222', $html);
        $this->assertStringContainsString('Universidad de Buenos Aires', $html);
    }

    public function test_se_puede_agregar_una_anotacion_a_la_bitacora(): void
    {
        $enrollment = CourseEnrollment::factory()->create();

        $this->tabla()->callAction(
            TestAction::make('bitacora')->table($enrollment),
            ['nota' => 'Pago confirmado por Mercado Pago, alias mp.alumno'],
        );

        $this->assertDatabaseHas('enrollment_notes', [
            'enrollment_id' => $enrollment->getKey(),
            'body' => 'Pago confirmado por Mercado Pago, alias mp.alumno',
        ]);
    }

    /** La bitácora es interna: agregar una anotación no encola ningún email. */
    public function test_la_bitacora_no_manda_ningun_correo(): void
    {
        $enrollment = CourseEnrollment::factory()->create();

        $this->tabla()->callAction(
            TestAction::make('bitacora')->table($enrollment),
            ['nota' => 'No pagó todavía.'],
        );

        $this->assertSame(0, QueuedEmail::count());
    }

    public function test_la_bitacora_muestra_lo_ya_anotado(): void
    {
        $enrollment = CourseEnrollment::factory()->create();
        EnrollmentNote::factory()->for($enrollment, 'enrollment')->create(['body' => 'NO PAGÓ']);

        $modal = $this->tabla()->mountAction(TestAction::make('bitacora')->table($enrollment));

        $html = (string) $modal->instance()->getMountedAction()->getModalContent()->render();

        $this->assertStringContainsString('NO PAGÓ', $html);
    }

    public function test_se_puede_enviar_un_aviso_administrativo(): void
    {
        $enrollment = CourseEnrollment::factory()->create();

        $this->tabla()
            ->callAction(TestAction::make('avisar')->table($enrollment), [
                'asunto' => 'Falta un dato en tu inscripción',
                'mensaje' => 'Necesitamos que nos confirmes tu número de socio.',
            ])
            ->assertHasNoActionErrors();

        $aviso = QueuedEmail::where('recipient_id', $enrollment->student->id)->firstOrFail();

        $this->assertSame(EmailType::AdministrativeNotice, $aviso->email_type);
        $this->assertSame('Falta un dato en tu inscripción', $aviso->subject);
        $this->assertStringContainsString('Necesitamos que nos confirmes tu número de socio.', $aviso->body);
    }
}
