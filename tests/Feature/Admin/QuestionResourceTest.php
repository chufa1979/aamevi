<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use Livewire\Livewire;
use App\Models\Question;
use App\Models\CourseClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\Questions\Pages\CreateQuestion;

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
