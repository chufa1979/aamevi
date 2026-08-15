<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\Quiz;
use App\Models\User;
use App\Models\Course;
use Livewire\Livewire;
use App\Models\Question;
use App\Models\CourseClass;
use App\Models\CourseModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\Courses\Pages\CourseExams;

class CourseExamsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    /** Un módulo con `$clases` clases de `$preguntas` preguntas cada una. */
    private function moduloCon(Course $course, int $clases, int $preguntas, int $orden = 1): CourseModule
    {
        $module = CourseModule::factory()->for($course)->create(['order_number' => $orden]);

        foreach (range(1, $clases) as $i) {
            $class = CourseClass::factory()->for($module, 'module')->create(['order_number' => $i]);

            if ($preguntas > 0) {
                Question::factory()->count($preguntas)->withOptions()->create(['class_id' => $class->id]);
            }
        }

        return $module;
    }

    public function test_lista_los_examenes_de_modulo_del_curso(): void
    {
        $course = Course::factory()->create();
        $module = $this->moduloCon($course, 2, 3);

        $examen = Quiz::factory()->create(['class_id' => null, 'module_id' => $module->id, 'questions_percentage' => 50]);

        Livewire::test(CourseExams::class, ['record' => $course->getKey()])
            ->assertCanSeeTableRecords([$examen]);
    }

    /** Las autoevaluaciones de clase se configuran en la clase, no acá. */
    public function test_no_lista_las_autoevaluaciones_de_clase(): void
    {
        $course = Course::factory()->create();
        $module = $this->moduloCon($course, 1, 2);
        $class = $module->classes()->first();

        $autoevaluacion = Quiz::factory()->create(['class_id' => $class->id]);

        Livewire::test(CourseExams::class, ['record' => $course->getKey()])
            ->assertCanNotSeeTableRecords([$autoevaluacion]);
    }

    public function test_muestra_el_tamano_del_banco_y_cuantas_sortea(): void
    {
        $course = Course::factory()->create();
        // 5 clases × 5 preguntas = 25; al 40% son 10
        $module = $this->moduloCon($course, 5, 5);

        $examen = Quiz::factory()->create([
            'class_id' => null,
            'module_id' => $module->id,
            'questions_percentage' => 40,
        ]);

        $this->assertSame(25, $examen->poolSize());
        $this->assertSame(10, $examen->questionsToDraw());

        Livewire::test(CourseExams::class, ['record' => $course->getKey()])
            ->assertSee('25')
            ->assertSee('10');
    }

    /**
     * Un examen sin banco se aprueba solo. Es el aviso que justifica la
     * pantalla: hasta ahora no había forma de darse cuenta desde el panel.
     */
    public function test_avisa_cuando_el_examen_no_tiene_preguntas(): void
    {
        $course = Course::factory()->create();
        $module = $this->moduloCon($course, 2, 0);

        Quiz::factory()->create(['class_id' => null, 'module_id' => $module->id, 'questions_percentage' => 50]);

        Livewire::test(CourseExams::class, ['record' => $course->getKey()])
            ->assertSee('Sin preguntas');
    }

    public function test_un_curso_sin_examenes_lo_dice(): void
    {
        $course = Course::factory()->create();
        $this->moduloCon($course, 1, 1);

        Livewire::test(CourseExams::class, ['record' => $course->getKey()])
            ->assertSee('Ningún módulo de este curso tiene examen');
    }
}
