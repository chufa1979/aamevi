<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use Livewire\Livewire;
use App\Models\CourseClass;
use App\Models\CourseModule;
use Filament\Actions\Testing\TestAction;
use Livewire\Features\SupportTesting\Testable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\CourseModules\Pages\ManageModuleClasses;

/**
 * El cronograma avanza en el tiempo: una clase no puede habilitarse antes que
 * la que va delante suyo.
 *
 * Cargar una clase con fecha anterior a la última la deja invisible hasta que
 * llegue su turno, o peor, disponible fuera de secuencia: el alumno vería la
 * clase 5 antes que la 4.
 */
class ClassScheduleValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    private function moduloConUnaClaseEl(string $fecha): CourseModule
    {
        $module = CourseModule::factory()->create();

        CourseClass::factory()->for($module, 'module')->create([
            'order_number' => 1,
            'activation_date' => $fecha,
        ]);

        return $module;
    }

    /** @param array<string, mixed> $extra */
    private function altaDeClase(CourseModule $module, string $fecha, array $extra = []): Testable
    {
        return Livewire::test(ManageModuleClasses::class, ['record' => $module->getKey()])
            ->callAction(TestAction::make('create')->table(), data: [
                'title' => 'Clase nueva',
                'activation_date' => $fecha,
                'is_live_session' => false,
                ...$extra,
            ]);
    }

    public function test_no_se_puede_dar_de_alta_una_clase_anterior_a_la_ultima(): void
    {
        $module = $this->moduloConUnaClaseEl('2026-09-10 10:00:00');

        $this->altaDeClase($module, '2026-09-03 10:00:00')
            ->assertHasActionErrors(['activation_date']);

        $this->assertDatabaseMissing('classes', ['title' => 'Clase nueva']);
    }

    /** «A lo sumo el mismo día»: dos clases el mismo día son normales. */
    public function test_se_puede_dar_de_alta_una_clase_el_mismo_dia_que_la_ultima(): void
    {
        $module = $this->moduloConUnaClaseEl('2026-09-10 10:00:00');

        $this->altaDeClase($module, '2026-09-10 18:00:00')
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('classes', ['title' => 'Clase nueva']);
    }

    /** Incluso más temprano en el mismo día: la comparación es por día, no por hora. */
    public function test_el_mismo_dia_vale_aunque_sea_mas_temprano(): void
    {
        $module = $this->moduloConUnaClaseEl('2026-09-10 18:00:00');

        $this->altaDeClase($module, '2026-09-10 09:00:00')
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('classes', ['title' => 'Clase nueva']);
    }

    public function test_se_puede_dar_de_alta_una_clase_posterior(): void
    {
        $module = $this->moduloConUnaClaseEl('2026-09-10 10:00:00');

        $this->altaDeClase($module, '2026-09-17 10:00:00')
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('classes', ['title' => 'Clase nueva']);
    }

    /** La primera clase del módulo no tiene con qué chocar. */
    public function test_la_primera_clase_puede_ir_cuando_sea(): void
    {
        $module = CourseModule::factory()->create();

        $this->altaDeClase($module, '2020-01-01 10:00:00')
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('classes', ['title' => 'Clase nueva']);
    }

    /**
     * Sin esto la validación tendría un agujero: alcanzaría con dar de alta la
     * clase con fecha válida y después editarla hacia atrás.
     */
    public function test_tampoco_se_puede_editar_una_clase_hacia_antes_de_la_anterior(): void
    {
        $module = $this->moduloConUnaClaseEl('2026-09-10 10:00:00');

        $segunda = CourseClass::factory()->for($module, 'module')->create([
            'order_number' => 2,
            'activation_date' => '2026-09-17 10:00:00',
        ]);

        Livewire::test(ManageModuleClasses::class, ['record' => $module->getKey()])
            ->callAction(TestAction::make('edit')->table($segunda), data: [
                'title' => $segunda->title,
                'activation_date' => '2026-09-03 10:00:00',
                'is_live_session' => false,
            ])
            ->assertHasActionErrors(['activation_date']);

        $this->assertSame('2026-09-17', $segunda->refresh()->activation_date->toDateString());
    }

    /** Editar la primera de un módulo no puede quedar trabada por las que le siguen. */
    public function test_la_primera_clase_se_puede_editar_libremente(): void
    {
        $module = $this->moduloConUnaClaseEl('2026-09-10 10:00:00');
        $primera = $module->classes()->first();

        CourseClass::factory()->for($module, 'module')->create([
            'order_number' => 2,
            'activation_date' => '2026-09-17 10:00:00',
        ]);

        Livewire::test(ManageModuleClasses::class, ['record' => $module->getKey()])
            ->callAction(TestAction::make('edit')->table($primera), data: [
                'title' => $primera->title,
                'activation_date' => '2026-09-01 10:00:00',
                'is_live_session' => false,
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('2026-09-01', $primera->refresh()->activation_date->toDateString());
    }

    public function test_la_fecha_minima_sale_de_la_clase_anterior(): void
    {
        $module = $this->moduloConUnaClaseEl('2026-09-10 18:30:00');

        CourseClass::factory()->for($module, 'module')->create([
            'order_number' => 2,
            'activation_date' => '2026-09-17 09:00:00',
        ]);

        // Para una clase nueva, la referencia es la última cargada
        $this->assertSame('2026-09-17 00:00:00', $module->earliestDateFor(3)->toDateTimeString());

        // Para la segunda, la primera
        $this->assertSame('2026-09-10 00:00:00', $module->earliestDateFor(2)->toDateTimeString());

        // Para la primera, ninguna
        $this->assertNull($module->earliestDateFor(1));
    }
}
