<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Quiz;
use App\Models\Student;
use App\Models\Question;
use App\Models\CourseClass;
use App\Services\QuizService;
use App\Exceptions\QuizException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class QuizAttemptTest extends TestCase
{
    use RefreshDatabase;

    private QuizService $quizzes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->quizzes = app(QuizService::class);
    }

    /** Un quiz de N preguntas, todas con 4 opciones y una correcta. */
    private function quizCon(int $preguntas, array $config = []): Quiz
    {
        $class = CourseClass::factory()->create();

        Question::factory()->count($preguntas)->withOptions()->create(['class_id' => $class->id]);

        return Quiz::factory()->create([
            'class_id' => $class->id,
            'questions_per_student' => $preguntas,
            ...$config,
        ]);
    }

    /** @return array<string, string> question_id => option_id */
    private function respuestas(Quiz $quiz, $attempt, bool $correctas): array
    {
        return $attempt->questions->mapWithKeys(fn (Question $q): array => [
            $q->id => $q->options->firstWhere('is_correct', $correctas)->id,
        ])->all();
    }

    public function test_empezar_un_intento_sortea_y_registra_las_preguntas(): void
    {
        $quiz = $this->quizCon(5);
        $student = Student::factory()->create();

        $attempt = $this->quizzes->start($quiz, $student);

        $this->assertSame(1, $attempt->attempt_number);
        $this->assertTrue($attempt->isInProgress());
        $this->assertCount(5, $attempt->questions);

        // El orden queda registrado: es lo que permite reconstruir el intento
        $this->assertSame([1, 2, 3, 4, 5], $attempt->questions->pluck('pivot.assigned_order')->all());
    }

    public function test_recargar_no_consume_un_intento_ni_cambia_las_preguntas(): void
    {
        $quiz = $this->quizCon(3);
        $student = Student::factory()->create();

        $primero = $this->quizzes->start($quiz, $student);
        $segundo = $this->quizzes->start($quiz, $student);

        $this->assertTrue($primero->is($segundo));
        $this->assertDatabaseCount('student_quiz_attempts', 1);
    }

    public function test_responder_todo_bien_da_cien(): void
    {
        $quiz = $this->quizCon(4);
        $student = Student::factory()->create();

        $attempt = $this->quizzes->start($quiz, $student);
        $attempt = $this->quizzes->submit($attempt, $this->respuestas($quiz, $attempt, correctas: true));

        $this->assertSame(100, $attempt->score);
        $this->assertTrue($attempt->passed);
        $this->assertTrue($attempt->isSubmitted());
    }

    public function test_responder_todo_mal_da_cero(): void
    {
        $quiz = $this->quizCon(4);
        $student = Student::factory()->create();

        $attempt = $this->quizzes->start($quiz, $student);
        $attempt = $this->quizzes->submit($attempt, $this->respuestas($quiz, $attempt, correctas: false));

        $this->assertSame(0, $attempt->score);
        $this->assertFalse($attempt->passed);
    }

    public function test_la_nota_es_el_porcentaje_de_aciertos(): void
    {
        $quiz = $this->quizCon(4, ['passing_score' => 70]);
        $student = Student::factory()->create();

        $attempt = $this->quizzes->start($quiz, $student);

        // Dos de cuatro: 50%
        $respuestas = [];

        foreach ($attempt->questions as $i => $question) {
            $respuestas[$question->id] = $question->options
                ->firstWhere('is_correct', $i < 2)
                ->id;
        }

        $attempt = $this->quizzes->submit($attempt, $respuestas);

        $this->assertSame(50, $attempt->score);
        $this->assertFalse($attempt->passed, 'Con 50 no alcanza el mínimo de 70.');
    }

    public function test_las_preguntas_sin_responder_cuentan_como_error_pero_se_guardan(): void
    {
        $quiz = $this->quizCon(4);
        $student = Student::factory()->create();

        $attempt = $this->quizzes->start($quiz, $student);
        $attempt = $this->quizzes->submit($attempt, []);

        $this->assertSame(0, $attempt->score);

        // Se registran igual: sin esto no se distingue "no contestó" de "no se
        // le preguntó"
        $this->assertCount(4, $attempt->answers);
        $this->assertTrue($attempt->answers->every(fn ($a): bool => $a->selected_option_id === null));
    }

    public function test_no_se_puede_entregar_dos_veces(): void
    {
        $quiz = $this->quizCon(2);
        $student = Student::factory()->create();

        $attempt = $this->quizzes->start($quiz, $student);
        $attempt = $this->quizzes->submit($attempt, []);

        $this->expectException(QuizException::class);
        $this->expectExceptionMessage('ya fue entregado');

        $this->quizzes->submit($attempt, []);
    }

    public function test_no_se_puede_responder_una_pregunta_ajena_al_intento(): void
    {
        $quiz = $this->quizCon(2);
        $student = Student::factory()->create();
        $ajena = Question::factory()->withOptions()->create();

        $attempt = $this->quizzes->start($quiz, $student);

        $this->expectException(QuizException::class);
        $this->expectExceptionMessage('no formaba parte');

        $this->quizzes->submit($attempt, [$ajena->id => $ajena->options->first()->id]);
    }

    public function test_se_agotan_los_intentos(): void
    {
        $quiz = $this->quizCon(2, ['max_attempts' => 2]);
        $student = Student::factory()->create();

        foreach (range(1, 2) as $i) {
            $attempt = $this->quizzes->start($quiz, $student);
            $this->quizzes->submit($attempt, []);
        }

        $this->assertSame(0, $this->quizzes->attemptsLeft($quiz, $student));

        $this->expectException(QuizException::class);
        $this->expectExceptionMessage('los 2 intentos');

        $this->quizzes->start($quiz, $student);
    }

    public function test_los_intentos_se_numeran_en_orden(): void
    {
        $quiz = $this->quizCon(2, ['max_attempts' => 3]);
        $student = Student::factory()->create();

        foreach (range(1, 3) as $esperado) {
            $attempt = $this->quizzes->start($quiz, $student);
            $this->assertSame($esperado, $attempt->attempt_number);
            $this->quizzes->submit($attempt, []);
        }
    }

    public function test_no_se_puede_empezar_una_evaluacion_sin_preguntas(): void
    {
        $quiz = Quiz::factory()->create();

        $this->expectException(QuizException::class);
        $this->expectExceptionMessage('no tiene preguntas');

        $this->quizzes->start($quiz, Student::factory()->create());
    }

    public function test_aprobar_una_vez_alcanza_aunque_despues_desapruebe(): void
    {
        $quiz = $this->quizCon(2, ['max_attempts' => 3]);
        $student = Student::factory()->create();

        $primero = $this->quizzes->start($quiz, $student);
        $this->quizzes->submit($primero, $this->respuestas($quiz, $primero, correctas: true));

        $segundo = $this->quizzes->start($quiz, $student);
        $this->quizzes->submit($segundo, []);

        $this->assertTrue($this->quizzes->hasPassed($quiz, $student));
    }

    /** El intento tiene que quedar reconstruible mucho después. */
    public function test_queda_registrado_que_pregunta_se_respondio_y_con_que_opcion(): void
    {
        $quiz = $this->quizCon(3);
        $student = Student::factory()->create();

        $attempt = $this->quizzes->start($quiz, $student);
        $elegidas = $this->respuestas($quiz, $attempt, correctas: true);

        $attempt = $this->quizzes->submit($attempt, $elegidas);

        foreach ($attempt->answers as $answer) {
            $this->assertSame($elegidas[$answer->question_id], $answer->selected_option_id);
            $this->assertTrue($answer->is_correct);
        }

        $this->assertCount(3, $attempt->questions);
    }
}
