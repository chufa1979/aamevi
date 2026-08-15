<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use Livewire\Livewire;
use App\Models\CourseClass;
use App\Models\CourseModule;
use App\Services\ProgressService;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\Courses\Pages\ManageCourseContent;
use App\Filament\Resources\CourseModules\Pages\ManageModuleClasses;

/**
 * Reordenar módulos y clases arrastrando.
 *
 * El caso que hay que proteger es el intercambio: Filament escribe todas las
 * posiciones en un solo UPDATE, y asignarle el 1 a la fila que estaba segunda
 * choca contra el unique mientras la primera todavía lo tiene. `DragToReorder`
 * corre las filas fuera del rango antes de escribir; sin eso, esto explota con
 * una violación de integridad.
 */
class TableReorderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    /** @return array<int, CourseModule> */
    private function modulosDe(Course $course, int $cantidad): array
    {
        return collect(range(1, $cantidad))
            ->map(fn (int $i): CourseModule => CourseModule::factory()->for($course)->create([
                'order_number' => $i,
                'title' => "Módulo {$i}",
            ]))
            ->all();
    }

    public function test_intercambiar_dos_modulos_no_choca_contra_el_unique(): void
    {
        $course = Course::factory()->create();
        [$primero, $segundo] = $this->modulosDe($course, 2);

        Livewire::test(ManageCourseContent::class, ['record' => $course->getKey()])
            ->call('reorderTable', [$segundo->getKey(), $primero->getKey()]);

        $this->assertSame(1, $segundo->refresh()->order_number);
        $this->assertSame(2, $primero->refresh()->order_number);
    }

    public function test_mover_el_ultimo_modulo_al_principio_renumera_a_todos(): void
    {
        $course = Course::factory()->create();
        [$a, $b, $c] = $this->modulosDe($course, 3);

        Livewire::test(ManageCourseContent::class, ['record' => $course->getKey()])
            ->call('reorderTable', [$c->getKey(), $a->getKey(), $b->getKey()]);

        $this->assertSame(
            ['Módulo 3', 'Módulo 1', 'Módulo 2'],
            $course->modules()->orderBy('order_number')->pluck('title')->all(),
        );
    }

    public function test_reordenar_las_clases_de_un_modulo(): void
    {
        $module = CourseModule::factory()->create();

        $clases = collect(range(1, 3))
            ->map(fn (int $i): CourseClass => CourseClass::factory()->for($module, 'module')->create([
                'order_number' => $i,
                'title' => "Clase {$i}",
            ]));

        Livewire::test(ManageModuleClasses::class, ['record' => $module->getKey()])
            ->call('reorderTable', [
                $clases[2]->getKey(),
                $clases[1]->getKey(),
                $clases[0]->getKey(),
            ]);

        $this->assertSame(
            ['Clase 3', 'Clase 2', 'Clase 1'],
            $module->classes()->orderBy('order_number')->pluck('title')->all(),
        );
    }

    /** Ya no se escribe el número: el módulo nuevo va al final solo. */
    public function test_un_modulo_nuevo_queda_al_final_sin_indicar_la_posicion(): void
    {
        $course = Course::factory()->create();
        $this->modulosDe($course, 2);

        Livewire::test(ManageCourseContent::class, ['record' => $course->getKey()])
            ->callAction(TestAction::make('create')->table(), data: ['title' => 'Módulo agregado'])
            ->assertHasNoActionErrors();

        $nuevo = $course->modules()->where('title', 'Módulo agregado')->firstOrFail();

        $this->assertSame(3, $nuevo->order_number);
    }

    /**
     * El orden de las clases no es decorativo: es la cadena que decide qué
     * clase habilita a cuál. Reordenar tiene que mover el gateo con ella.
     */
    public function test_reordenar_cambia_que_clase_habilita_a_cual(): void
    {
        $progreso = app(ProgressService::class);

        $course = Course::factory()->create();
        $module = CourseModule::factory()->for($course)->create(['order_number' => 1]);

        $primera = CourseClass::factory()->for($module, 'module')->create([
            'order_number' => 1,
            'title' => 'Primera',
            'activation_date' => now()->subDay(),
        ]);

        $segunda = CourseClass::factory()->for($module, 'module')->create([
            'order_number' => 2,
            'title' => 'Segunda',
            'activation_date' => now()->subDay(),
        ]);

        $this->assertTrue($primera->is($progreso->previousClass($segunda)));

        Livewire::test(ManageModuleClasses::class, ['record' => $module->getKey()])
            ->call('reorderTable', [$segunda->getKey(), $primera->getKey()]);

        $this->assertTrue(
            $segunda->refresh()->is($progreso->previousClass($primera->refresh())),
            'Después de invertirlas, la que ahora va primera tiene que habilitar a la otra.',
        );
    }

    /** Arrastrar entre páginas no existe: la lista va entera. */
    public function test_el_listado_de_modulos_no_pagina(): void
    {
        $course = Course::factory()->create();
        $this->modulosDe($course, 12);

        Livewire::test(ManageCourseContent::class, ['record' => $course->getKey()])
            ->assertCanSeeTableRecords($course->modules()->get());
    }

    public function test_un_alumno_ve_las_clases_en_el_orden_nuevo(): void
    {
        $progreso = app(ProgressService::class);

        $course = Course::factory()->create();
        $module = CourseModule::factory()->for($course)->create(['order_number' => 1]);

        $clases = collect(range(1, 3))
            ->map(fn (int $i): CourseClass => CourseClass::factory()->for($module, 'module')->create([
                'order_number' => $i,
                'activation_date' => now()->subDay(),
            ]));

        Livewire::test(ManageModuleClasses::class, ['record' => $module->getKey()])
            ->call('reorderTable', [
                $clases[1]->getKey(),
                $clases[2]->getKey(),
                $clases[0]->getKey(),
            ]);

        // La primera del módulo, sea cual sea, no tiene anterior que aprobar
        $this->assertNull(
            $progreso->previousClass($clases[1]->refresh()),
            'La clase que quedó primera no debería tener anterior.',
        );

        // Y la que quedó última sí, en la posición nueva
        $this->assertTrue(
            $clases[2]->refresh()->is($progreso->previousClass($clases[0]->refresh())),
        );
    }
}
