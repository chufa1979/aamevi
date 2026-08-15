<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use Livewire\Livewire;
use App\Models\Question;
use App\Models\CourseClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\Questions\Pages\CreateQuestion;
use App\Filament\Resources\CourseClasses\Pages\ManageClassQuestions;

class QuestionResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_el_listado_muestra_las_preguntas_con_su_contexto(): void
    {
        $question = Question::factory()->withOptions()->create(['text' => '¿Qué es la medicina del estilo de vida?']);

        $this->get('/admin/questions')
            ->assertSuccessful()
            ->assertSee('¿Qué es la medicina del estilo de vida?')
            ->assertSee($question->class->title);
    }

    public function test_se_puede_cargar_una_pregunta_con_sus_opciones(): void
    {
        $class = CourseClass::factory()->create();

        Livewire::test(CreateQuestion::class)
            ->fillForm([
                'class_id' => $class->id,
                'text' => '¿Cuántos pilares tiene la medicina del estilo de vida?',
                'is_active' => true,
                'options' => [
                    ['option_text' => 'Cuatro', 'is_correct' => false],
                    ['option_text' => 'Seis', 'is_correct' => true],
                    ['option_text' => 'Ocho', 'is_correct' => false],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $question = Question::where('class_id', $class->id)->firstOrFail();

        $this->assertCount(3, $question->options);
        $this->assertSame('Seis', $question->correctOption()->option_text);
    }

    /**
     * La pantalla de la clase es el otro camino para cargar preguntas. Sin este
     * test, un import faltante ahí no lo detecta nadie: los demás entran por el
     * listado general.
     */
    public function test_la_pantalla_de_la_clase_muestra_sus_preguntas(): void
    {
        $question = Question::factory()->withOptions()->create([
            'text' => '<p>Enunciado de la <strong>clase</strong></p>',
        ]);

        // La página en sí: un import faltante en el relation manager la tira
        $this->get('/admin/course-classes/'.$question->class_id.'/edit')
            ->assertSuccessful()
            ->assertSee('Preguntas');

        // Y la tabla, que Livewire carga aparte
        Livewire::test(ManageClassQuestions::class, ['record' => $question->class_id])->assertCanSeeTableRecords([$question]);
    }

    public function test_el_enunciado_conserva_el_texto_enriquecido(): void
    {
        $class = CourseClass::factory()->create();

        Livewire::test(CreateQuestion::class)
            ->fillForm([
                'class_id' => $class->id,
                'text' => '<p>¿Cuál es la dosis <strong>mínima</strong> de actividad <em>aeróbica</em> semanal?</p>',
                'is_active' => true,
                'options' => [
                    ['option_text' => '75 minutos', 'is_correct' => false],
                    ['option_text' => '150 minutos', 'is_correct' => true],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $text = Question::where('class_id', $class->id)->firstOrFail()->text;

        $this->assertStringContainsString('<strong>mínima</strong>', $text);
        $this->assertStringContainsString('<em>aeróbica</em>', $text);
    }

    /** El listado muestra el enunciado sin las etiquetas del editor. */
    public function test_el_listado_no_muestra_el_marcado(): void
    {
        Question::factory()->withOptions()->create([
            'text' => '<p>Pregunta con <strong>formato</strong></p>',
        ]);

        $this->get('/admin/questions')
            ->assertSuccessful()
            ->assertSee('Pregunta con formato')
            ->assertDontSee('<strong>formato</strong>', escape: false);
    }

    public function test_no_se_puede_guardar_sin_marcar_una_correcta(): void
    {
        $class = CourseClass::factory()->create();

        Livewire::test(CreateQuestion::class)
            ->fillForm([
                'class_id' => $class->id,
                'text' => 'Pregunta sin respuesta correcta',
                'options' => [
                    ['option_text' => 'Una', 'is_correct' => false],
                    ['option_text' => 'Otra', 'is_correct' => false],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['options']);

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_no_se_puede_guardar_con_dos_correctas(): void
    {
        $class = CourseClass::factory()->create();

        Livewire::test(CreateQuestion::class)
            ->fillForm([
                'class_id' => $class->id,
                'text' => 'Pregunta ambigua',
                'options' => [
                    ['option_text' => 'Una', 'is_correct' => true],
                    ['option_text' => 'Otra', 'is_correct' => true],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['options']);

        $this->assertDatabaseCount('questions', 0);
    }
}
