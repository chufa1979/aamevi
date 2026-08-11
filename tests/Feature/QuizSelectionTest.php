<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\CourseClass;
use App\Models\CourseModule;
use App\Exceptions\QuizException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class QuizSelectionTest extends TestCase
{
    use RefreshDatabase;

    /** Crea un módulo con N clases de M preguntas cada una. */
    private function moduloCon(int $clases, int $preguntasPorClase): CourseModule
    {
        $module = CourseModule::factory()->create();

        foreach (range(1, $clases) as $i) {
            $class = CourseClass::factory()->for($module, 'module')->create(['order_number' => $i]);
            Question::factory()->count($preguntasPorClase)->create(['class_id' => $class->id]);
        }

        return $module;
    }

    public function test_el_quiz_de_clase_sortea_las_preguntas_configuradas(): void
    {
        $class = CourseClass::factory()->create();
        Question::factory()->count(10)->create(['class_id' => $class->id]);

        $quiz = Quiz::factory()->create([
            'class_id' => $class->id,
            'questions_per_student' => 5,
        ]);

        $this->assertSame(10, $quiz->poolSize());
        $this->assertSame(5, $quiz->questionsToDraw());
        $this->assertCount(5, $quiz->drawQuestions());
    }

    public function test_el_quiz_de_clase_solo_ve_preguntas_de_su_clase(): void
    {
        $class = CourseClass::factory()->create();
        Question::factory()->count(3)->create(['class_id' => $class->id]);
        Question::factory()->count(7)->create(); // otra clase

        $quiz = Quiz::factory()->create(['class_id' => $class->id]);

        $this->assertSame(3, $quiz->poolSize());
    }

    /** El caso que se planteó: 5 clases de 5 preguntas, examen del módulo. */
    public function test_el_examen_de_modulo_mezcla_las_preguntas_de_todas_sus_clases(): void
    {
        $module = $this->moduloCon(clases: 5, preguntasPorClase: 5);

        $quiz = Quiz::factory()->forModule($module, percentage: 40)->create();

        $this->assertSame(25, $quiz->poolSize(), 'El banco combinado son las 25 preguntas del módulo.');
        $this->assertSame(10, $quiz->questionsToDraw(), '40% de 25 son 10 preguntas.');

        $preguntas = $quiz->drawQuestions();

        $this->assertCount(10, $preguntas);

        // Y salen de más de una clase: si viniesen todas de la misma, el examen
        // no estaría evaluando el módulo.
        $this->assertGreaterThan(1, $preguntas->pluck('class_id')->unique()->count());
    }

    public function test_el_porcentaje_redondea_hacia_arriba(): void
    {
        $module = $this->moduloCon(clases: 2, preguntasPorClase: 5);

        // 30% de 10 son 3; 33% de 10 son 3,3 y se toman 4
        $this->assertSame(3, Quiz::factory()->forModule($module, 30)->create()->questionsToDraw());

        Quiz::query()->delete();

        $this->assertSame(4, Quiz::factory()->forModule($module, 33)->create()->questionsToDraw());
    }

    public function test_el_ciento_por_ciento_toma_todo_el_banco(): void
    {
        $module = $this->moduloCon(clases: 3, preguntasPorClase: 4);

        $quiz = Quiz::factory()->forModule($module, 100)->create();

        $this->assertSame(12, $quiz->questionsToDraw());
    }

    /**
     * Pedir más preguntas que las que hay devolvería menos y calificaría sobre
     * un total distinto al configurado.
     */
    public function test_nunca_sortea_mas_preguntas_que_las_disponibles(): void
    {
        $class = CourseClass::factory()->create();
        Question::factory()->count(3)->create(['class_id' => $class->id]);

        $quiz = Quiz::factory()->create([
            'class_id' => $class->id,
            'questions_per_student' => 10,
        ]);

        $this->assertSame(3, $quiz->questionsToDraw());
        $this->assertCount(3, $quiz->drawQuestions());
    }

    /** Un examen de cero preguntas se aprobaría solo. */
    public function test_un_porcentaje_minimo_igual_sortea_al_menos_una(): void
    {
        $module = $this->moduloCon(clases: 1, preguntasPorClase: 4);

        $quiz = Quiz::factory()->forModule($module, 1)->create();

        $this->assertSame(1, $quiz->questionsToDraw());
    }

    public function test_las_preguntas_inactivas_no_entran_al_sorteo(): void
    {
        $class = CourseClass::factory()->create();
        Question::factory()->count(4)->create(['class_id' => $class->id]);
        Question::factory()->count(6)->inactive()->create(['class_id' => $class->id]);

        $quiz = Quiz::factory()->create(['class_id' => $class->id]);

        $this->assertSame(4, $quiz->poolSize());
    }

    public function test_un_quiz_sin_preguntas_no_esta_listo(): void
    {
        $quiz = Quiz::factory()->create();

        $this->assertFalse($quiz->isReady());
        $this->assertSame(0, $quiz->questionsToDraw());
        $this->assertCount(0, $quiz->drawQuestions());
    }

    public function test_un_quiz_no_puede_colgar_de_una_clase_y_de_un_modulo(): void
    {
        $this->expectException(QuizException::class);

        Quiz::create([
            'class_id' => CourseClass::factory()->create()->id,
            'module_id' => CourseModule::factory()->create()->id,
        ]);
    }

    public function test_un_quiz_tiene_que_colgar_de_algo(): void
    {
        $this->expectException(QuizException::class);

        Quiz::create(['title' => 'Huérfano']);
    }

    public function test_borrar_la_clase_arrastra_sus_preguntas_y_su_quiz(): void
    {
        $class = CourseClass::factory()->create();
        $question = Question::factory()->withOptions()->create(['class_id' => $class->id]);
        $quiz = Quiz::factory()->create(['class_id' => $class->id]);

        $class->delete();

        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
        $this->assertDatabaseMissing('question_options', ['question_id' => $question->id]);
        $this->assertDatabaseMissing('quizzes', ['id' => $quiz->id]);
    }
}
