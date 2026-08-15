<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use Livewire\Livewire;
use App\Models\CourseClass;
use App\Models\CourseModule;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\Courses\Pages\CourseSchedule;

/**
 * El cronograma del curso entero.
 *
 * Es la pantalla que contesta «qué se dicta la semana que viene», que mirando
 * módulo por módulo no se contesta.
 */
class CourseSchedulePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    /** Dos módulos con una clase cada uno. */
    private function cursoConDosModulos(): Course
    {
        $course = Course::factory()->create();

        foreach ([1, 2] as $orden) {
            $module = CourseModule::factory()->for($course)->create(['order_number' => $orden]);

            CourseClass::factory()->for($module, 'module')->create([
                'title' => "Clase del módulo {$orden}",
                'order_number' => 1,
                'activation_date' => now()->addDays($orden),
            ]);
        }

        return $course;
    }

    /** Sin esto, para ver el cronograma habría que abrir módulo por módulo. */
    public function test_lista_las_clases_de_todos_los_modulos_juntas(): void
    {
        $course = $this->cursoConDosModulos();

        Livewire::test(CourseSchedule::class, ['record' => $course->getKey()])
            ->assertCanSeeTableRecords($course->classes()->get());
    }

    public function test_no_muestra_las_clases_de_otro_curso(): void
    {
        $course = $this->cursoConDosModulos();
        $ajena = CourseClass::factory()->create();

        Livewire::test(CourseSchedule::class, ['record' => $course->getKey()])
            ->assertCanNotSeeTableRecords([$ajena]);
    }

    public function test_correr_fechas_reprograma_las_clases_seleccionadas(): void
    {
        $course = $this->cursoConDosModulos();
        $clases = $course->classes()->get();
        $original = $clases->first()->activation_date;

        Livewire::test(CourseSchedule::class, ['record' => $course->getKey()])
            ->selectTableRecords($clases->pluck('id')->all())
            ->callAction(TestAction::make('shiftDates')->table()->bulk(), data: ['days' => 7])
            ->assertHasNoActionErrors();

        $this->assertTrue(
            $clases->first()->refresh()->activation_date->equalTo($original->copy()->addDays(7)),
            'La fecha no se corrió siete días.',
        );
    }

    public function test_se_pueden_filtrar_las_clases_ya_habilitadas(): void
    {
        $course = Course::factory()->create();
        $module = CourseModule::factory()->for($course)->create();

        $abierta = CourseClass::factory()->for($module, 'module')->create([
            'order_number' => 1,
            'activation_date' => now()->subWeek(),
        ]);

        $futura = CourseClass::factory()->for($module, 'module')->create([
            'order_number' => 2,
            'activation_date' => now()->addWeek(),
        ]);

        Livewire::test(CourseSchedule::class, ['record' => $course->getKey()])
            ->filterTable('disponible', true)
            ->assertCanSeeTableRecords([$abierta])
            ->assertCanNotSeeTableRecords([$futura]);
    }
}
