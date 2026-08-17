<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Quiz;
use App\Models\Course;
use App\Models\Student;
use App\Enums\EmailType;
use App\Models\Question;
use App\Models\CourseClass;
use App\Models\QueuedEmail;
use App\Models\QuizAttempt;
use App\Models\CourseModule;
use App\Services\QuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Devolverle los intentos a un alumno que se trabó.
 *
 * Un alumno que agota los intentos sin aprobar no puede seguir el curso: la
 * progresión exige aprobar la autoevaluación. El aula le decía «hablá con tu
 * docente» y el docente no tenía nada que hacer.
 *
 * **No se borra nada.** El reseteo abre un ciclo nuevo: los intentos anteriores
 * siguen en el historial —son la prueba de sobre qué se lo calificó— pero dejan
 * de ocupar cupo.
 */
class AttemptResetTest extends TestCase
{
    use RefreshDatabase;

    private QuizService $quizzes;

    private Quiz $quiz;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->quizzes = app(QuizService::class);

        $course = Course::factory()->create();
        $module = CourseModule::factory()->for($course)->create(['order_number' => 1]);
        $class = CourseClass::factory()->for($module, 'module')->create(['order_number' => 1]);

        Question::factory()->count(3)->withOptions()->create(['class_id' => $class->getKey()]);

        $this->quiz = Quiz::factory()->create([
            'class_id' => $class->getKey(),
            'questions_per_student' => 2,
            'passing_score' => 60,
            'max_attempts' => 3,
        ]);

        $this->student = Student::factory()->create();
    }

    private function rendir(bool $bien): QuizAttempt
    {
        $attempt = $this->quizzes->start($this->quiz, $this->student);

        return $this->quizzes->submit($attempt, $attempt->questions->mapWithKeys(
            fn (Question $q): array => [$q->id => $q->options->firstWhere('is_correct', $bien)->id],
        )->all());
    }

    private function agotar(): void
    {
        foreach (range(1, 3) as $i) {
            $this->rendir(false);
        }
    }

    // ── El límite ───────────────────────────────────────────────────────────

    public function test_sin_reseteos_se_comporta_como_antes(): void
    {
        $this->assertSame(3, $this->quizzes->attemptsLeft($this->quiz, $this->student));

        $this->rendir(false);

        $this->assertSame(2, $this->quizzes->attemptsLeft($this->quiz, $this->student));
    }

    public function test_el_reseteo_le_devuelve_todos_los_intentos(): void
    {
        $this->agotar();
        $this->assertSame(0, $this->quizzes->attemptsLeft($this->quiz, $this->student));

        $this->travel(1)->minute();
        $this->quizzes->reset($this->quiz, $this->student);

        $this->assertSame(3, $this->quizzes->attemptsLeft($this->quiz, $this->student));
    }

    public function test_despues_del_reseteo_puede_volver_a_rendir(): void
    {
        $this->agotar();
        $this->travel(1)->minute();
        $this->quizzes->reset($this->quiz, $this->student);

        $cuarto = $this->rendir(true);

        $this->assertTrue($cuarto->passed);
    }

    /**
     * El número de intento cuenta todos los que hizo, no los del ciclo.
     *
     * Hay un `unique(quiz_id, student_id, attempt_number)`: reiniciar la
     * numeración después de un reseteo chocaría contra él.
     */
    public function test_la_numeracion_sigue_de_largo_y_no_choca_contra_el_unique(): void
    {
        $this->agotar();
        $this->travel(1)->minute();
        $this->quizzes->reset($this->quiz, $this->student);

        $cuarto = $this->rendir(false);

        $this->assertSame(4, $cuarto->attempt_number);
        $this->assertSame(4, $this->quizzes->attemptsOf($this->quiz, $this->student)->count());
    }

    /** El historial completo queda: es la prueba de sobre qué se lo calificó. */
    public function test_el_reseteo_no_borra_los_intentos_anteriores(): void
    {
        $this->agotar();
        $this->travel(1)->minute();
        $this->quizzes->reset($this->quiz, $this->student);

        $this->assertSame(3, QuizAttempt::where('student_id', $this->student->getKey())->count());
        $this->assertSame(0, $this->quizzes->attemptsLeft($this->quiz, $this->student) - 3);
    }

    /** Aprobar es aprobar, aunque haya sido antes del reseteo. */
    public function test_aprobar_antes_del_reseteo_sigue_contando(): void
    {
        $this->rendir(true);
        $this->travel(1)->minute();
        $this->quizzes->reset($this->quiz, $this->student);

        $this->assertTrue($this->quizzes->hasPassed($this->quiz, $this->student));
    }

    public function test_dos_reseteos_seguidos_no_acumulan_intentos(): void
    {
        $this->agotar();
        $this->travel(1)->minute();

        $this->quizzes->reset($this->quiz, $this->student);
        $this->quizzes->reset($this->quiz, $this->student);

        $this->assertSame(3, $this->quizzes->attemptsLeft($this->quiz, $this->student));
    }

    // ── Quién queda trabado ─────────────────────────────────────────────────

    public function test_esta_trabado_el_que_agoto_sin_aprobar(): void
    {
        $this->agotar();

        $this->assertTrue($this->quizzes->isStuck($this->quiz, $this->student));
    }

    public function test_no_esta_trabado_el_que_aprobo(): void
    {
        $this->rendir(true);
        $this->rendir(false);
        $this->rendir(false);

        $this->assertFalse($this->quizzes->isStuck($this->quiz, $this->student));
    }

    // ── Los avisos ──────────────────────────────────────────────────────────

    public function test_agotar_sin_aprobar_le_avisa_al_docente(): void
    {
        $this->agotar();

        $aviso = QueuedEmail::where('email_type', EmailType::AttemptsExhausted)->firstOrFail();

        $this->assertSame(
            $this->quiz->course()->teacher->user->getKey(),
            $aviso->recipient_id,
        );
        $this->assertStringContainsString($this->student->user->full_name, $aviso->subject);
    }

    /** Sólo el último intento avisa: los dos primeros no trababan a nadie. */
    public function test_los_intentos_previos_no_avisan(): void
    {
        $this->rendir(false);
        $this->rendir(false);

        $this->assertSame(0, QueuedEmail::where('email_type', EmailType::AttemptsExhausted)->count());
    }

    public function test_agotar_habiendo_aprobado_no_avisa(): void
    {
        $this->rendir(true);
        $this->rendir(false);
        $this->rendir(false);

        $this->assertSame(0, QueuedEmail::where('email_type', EmailType::AttemptsExhausted)->count());
    }

    public function test_el_reseteo_le_avisa_al_alumno(): void
    {
        $this->agotar();
        QueuedEmail::query()->delete();

        $this->quizzes->reset($this->quiz, $this->student, null, 'Se le cortó internet.');

        $aviso = QueuedEmail::where('email_type', EmailType::AttemptsReset)->firstOrFail();

        $this->assertSame($this->student->getKey(), $aviso->recipient_id);
        $this->assertStringContainsString('Se le cortó internet.', $aviso->body);
    }
}
