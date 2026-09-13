<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use Livewire\Livewire;
use Filament\Facades\Filament;
use App\Models\CourseEnrollment;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\EnrollmentRequests\Pages\ManageEnrollmentRequests;

/**
 * Ver alumno en Solicitudes: la ficha completa sin salir a `StudentResource`
 * a buscarlo. La bitácora y el aviso administrativo son del alumno, no de la
 * solicitud puntual — viven en `StudentsTable` (ver `StudentResourceTest`).
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

    public function test_ver_alumno_muestra_su_ficha(): void
    {
        $enrollment = CourseEnrollment::factory()->create();
        $enrollment->student->update(['dni' => '30111222', 'university' => 'Universidad de Buenos Aires']);

        $modal = Livewire::test(ManageEnrollmentRequests::class)
            ->mountAction(TestAction::make('ver_alumno')->table($enrollment));

        $html = (string) $modal->instance()->getMountedAction()->getModalContent()->render();

        $this->assertStringContainsString('30111222', $html);
        $this->assertStringContainsString('Universidad de Buenos Aires', $html);
    }
}
