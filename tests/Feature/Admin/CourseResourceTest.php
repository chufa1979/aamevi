<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use Livewire\Livewire;
use App\Models\Teacher;
use App\Models\CourseClass;
use App\Models\CourseModule;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\Courses\Pages\CreateCourse;
use App\Filament\Resources\Courses\Pages\ManageCourseContent;
use App\Filament\Resources\CourseModules\Pages\ManageModuleClasses;

class CourseResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_el_listado_muestra_los_cursos_con_su_docente(): void
    {
        $course = Course::factory()->create(['title' => 'Medicina del estilo de vida']);
        $course->teacher->user->update(['first_name' => 'Marta', 'last_name' => 'Gómez']);

        $this->get('/admin/courses')
            ->assertSuccessful()
            ->assertSee('Medicina del estilo de vida')
            ->assertSee('Marta Gómez');
    }

    public function test_se_puede_crear_un_curso(): void
    {
        $teacher = Teacher::factory()->create();

        Livewire::test(CreateCourse::class)
            ->fillForm([
                'title' => 'Nutrición aplicada',
                'description' => 'Curso de prueba.',
                'teacher_id' => $teacher->id,
                'max_students' => 30,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('courses', [
            'title' => 'Nutrición aplicada',
            'teacher_id' => $teacher->id,
            'max_students' => 30,
        ]);
    }

    public function test_la_descripcion_del_curso_acepta_texto_enriquecido(): void
    {
        $teacher = Teacher::factory()->create();

        Livewire::test(CreateCourse::class)
            ->fillForm([
                'title' => 'Curso con formato',
                'description' => '<p>Dictado por <strong>especialistas</strong>.</p><ul><li>Módulo inicial</li></ul>',
                'teacher_id' => $teacher->id,
                'max_students' => 30,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $description = Course::where('title', 'Curso con formato')->firstOrFail()->description;

        $this->assertStringContainsString('<strong>especialistas</strong>', $description);
        $this->assertStringContainsString('<ul>', $description);
    }

    public function test_la_descripcion_del_modulo_acepta_texto_enriquecido(): void
    {
        $course = Course::factory()->create();

        Livewire::test(ManageCourseContent::class, ['record' => $course->getKey()])
            ->callAction(TestAction::make('create')->table(), data: [
                'title' => 'Módulo con formato',
                'order_number' => 1,
                'description' => '<p>Contenido en <em>cursiva</em>.</p>',
            ])
            ->assertHasNoActionErrors();

        $description = CourseModule::where('title', 'Módulo con formato')->firstOrFail()->description;

        $this->assertStringContainsString('<em>cursiva</em>', $description);
    }

    public function test_se_puede_agregar_un_modulo_al_curso(): void
    {
        $course = Course::factory()->create();

        Livewire::test(ManageCourseContent::class, ['record' => $course->getKey()])
            ->callAction(TestAction::make('create')->table(), data: [
                'title' => 'Módulo 1 — Fundamentos',
                'order_number' => 1,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('modules', [
            'course_id' => $course->id,
            'title' => 'Módulo 1 — Fundamentos',
            'order_number' => 1,
        ]);
    }

    public function test_se_puede_agregar_una_clase_al_modulo(): void
    {
        $module = CourseModule::factory()->create();

        Livewire::test(ManageModuleClasses::class, ['record' => $module->getKey()])
            ->callAction(TestAction::make('create')->table(), data: [
                'title' => 'Clase 1 — Introducción',
                'order_number' => 1,
                'activation_date' => now()->format('Y-m-d H:i:s'),
                'is_live_session' => false,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('classes', [
            'module_id' => $module->id,
            'title' => 'Clase 1 — Introducción',
        ]);
    }

    public function test_correr_fechas_reprograma_las_clases_seleccionadas(): void
    {
        $module = CourseModule::factory()->create();

        $primera = CourseClass::factory()->for($module, 'module')->create([
            'order_number' => 1,
            'activation_date' => now()->startOfDay(),
        ]);
        $segunda = CourseClass::factory()->for($module, 'module')->create([
            'order_number' => 2,
            'activation_date' => now()->startOfDay()->addDay(),
        ]);

        $antesPrimera = $primera->activation_date->copy();
        $antesSegunda = $segunda->activation_date->copy();

        Livewire::test(ManageModuleClasses::class, ['record' => $module->getKey()])
            ->selectTableRecords([$primera->id, $segunda->id])
            ->callAction(
                TestAction::make('shiftDates')->table()->bulk(),
                data: ['days' => 7],
            )
            ->assertHasNoActionErrors();

        $this->assertTrue($antesPrimera->addDays(7)->equalTo($primera->refresh()->activation_date));
        $this->assertTrue($antesSegunda->addDays(7)->equalTo($segunda->refresh()->activation_date));
    }

    public function test_una_clase_futura_no_esta_disponible(): void
    {
        $futura = CourseClass::factory()->upcoming()->create();
        $actual = CourseClass::factory()->create(['activation_date' => now()->subDay()]);

        $this->assertFalse($futura->isAvailable());
        $this->assertTrue($actual->isAvailable());
    }

    public function test_borrar_un_curso_arrastra_modulos_y_clases(): void
    {
        $class = CourseClass::factory()->create();
        $module = $class->module;
        $course = $module->course;

        $course->delete();

        $this->assertDatabaseMissing('modules', ['id' => $module->id]);
        $this->assertDatabaseMissing('classes', ['id' => $class->id]);
    }
}
