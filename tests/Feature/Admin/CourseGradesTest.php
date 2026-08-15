<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use Livewire\Livewire;
use App\Models\Student;
use App\Models\CourseClass;
use App\Models\ClassContent;
use App\Models\CourseModule;
use App\Models\TaskSubmission;
use App\Enums\SubmissionStatus;
use Filament\Actions\Testing\TestAction;
use App\Filament\Resources\Courses\CourseResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\Courses\Pages\CourseGrades;

/**
 * La bandeja de entregas del curso.
 *
 * Lo que hay que proteger acá es la separación entre corregir y publicar: si la
 * nota se le escapa al alumno antes de tiempo, la función pierde todo el
 * sentido.
 */
class CourseGradesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    /**
     * Un curso con una tarea y la entrega de un alumno.
     *
     * El orden del módulo se calcula y no se fija en 1: llamarla dos veces sobre
     * el mismo curso chocaría contra el unique (course_id, order_number).
     */
    private function entregaDeUnCurso(Course $course): TaskSubmission
    {
        $module = CourseModule::factory()->for($course)->create([
            'order_number' => $course->modules()->max('order_number') + 1,
        ]);

        $class = CourseClass::factory()->for($module, 'module')->create(['order_number' => 1]);
        $tarea = ClassContent::factory()->task()->for($class, 'class')->create(['title' => 'Trabajo práctico 1']);

        return TaskSubmission::factory()->create([
            'content_id' => $tarea->id,
            'student_id' => Student::factory()->create()->id,
        ]);
    }

    public function test_la_solapa_abre(): void
    {
        $course = Course::factory()->create();

        $this->get(CourseResource::getUrl('grades', ['record' => $course]))->assertSuccessful();
    }

    public function test_lista_las_entregas_del_curso(): void
    {
        $course = Course::factory()->create();
        $entrega = $this->entregaDeUnCurso($course);

        Livewire::test(CourseGrades::class, ['record' => $course->getKey()])
            ->assertCanSeeTableRecords([$entrega]);
    }

    public function test_no_lista_las_entregas_de_otro_curso(): void
    {
        $course = Course::factory()->create();
        $ajena = $this->entregaDeUnCurso(Course::factory()->create());

        Livewire::test(CourseGrades::class, ['record' => $course->getKey()])
            ->assertCanNotSeeTableRecords([$ajena]);
    }

    public function test_corregir_pone_nota_y_devolucion(): void
    {
        $course = Course::factory()->create();
        $entrega = $this->entregaDeUnCurso($course);

        Livewire::test(CourseGrades::class, ['record' => $course->getKey()])
            ->callAction(TestAction::make('corregir')->table($entrega), data: [
                'nota' => 8,
                'aprobada' => true,
                'devolucion' => 'Buen desarrollo.',
            ])
            ->assertHasNoActionErrors();

        $entrega->refresh();

        $this->assertSame(SubmissionStatus::Approved, $entrega->status);
        $this->assertSame('8.00', $entrega->grade);
        $this->assertSame('Buen desarrollo.', $entrega->feedback);
    }

    /** Corregir no publica: es el punto de toda la función. */
    public function test_corregir_no_publica(): void
    {
        $course = Course::factory()->create();
        $entrega = $this->entregaDeUnCurso($course);

        Livewire::test(CourseGrades::class, ['record' => $course->getKey()])
            ->callAction(TestAction::make('corregir')->table($entrega), data: [
                'nota' => 8,
                'aprobada' => true,
            ]);

        $entrega->refresh();

        $this->assertTrue($entrega->isGraded());
        $this->assertFalse($entrega->isPublished());
        $this->assertFalse($entrega->isVisibleToStudent());
    }

    public function test_publicar_la_hace_visible(): void
    {
        $course = Course::factory()->create();
        $entrega = $this->entregaDeUnCurso($course);
        $entrega->update(['status' => SubmissionStatus::Approved, 'grade' => 9, 'graded_at' => now()]);

        Livewire::test(CourseGrades::class, ['record' => $course->getKey()])
            ->callAction(TestAction::make('publicar')->table($entrega))
            ->assertHasNoActionErrors();

        $this->assertTrue($entrega->refresh()->isVisibleToStudent());
    }

    /** La acción masiva es lo que vuelve usable publicar aparte de corregir. */
    public function test_publicar_en_tanda_solo_toca_las_corregidas(): void
    {
        $course = Course::factory()->create();

        $corregida = $this->entregaDeUnCurso($course);
        $corregida->update(['status' => SubmissionStatus::Approved, 'grade' => 7, 'graded_at' => now()]);

        $sinCorregir = $this->entregaDeUnCurso($course);

        Livewire::test(CourseGrades::class, ['record' => $course->getKey()])
            ->selectTableRecords([$corregida->id, $sinCorregir->id])
            ->callAction(TestAction::make('publicarSeleccionadas')->table()->bulk())
            ->assertHasNoActionErrors();

        $this->assertTrue($corregida->refresh()->isPublished());
        $this->assertFalse($sinCorregir->refresh()->isPublished());
    }

    public function test_no_se_ofrece_corregir_algo_ya_corregido(): void
    {
        $course = Course::factory()->create();
        $entrega = $this->entregaDeUnCurso($course);
        $entrega->update(['status' => SubmissionStatus::Approved, 'grade' => 7, 'graded_at' => now()]);

        Livewire::test(CourseGrades::class, ['record' => $course->getKey()])
            ->assertActionHidden(TestAction::make('corregir')->table($entrega));
    }
}
