<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\Quiz;
use App\Models\User;
use App\Models\Course;
use Livewire\Livewire;
use App\Models\Student;
use App\Models\Question;
use App\Models\CourseClass;
use App\Models\QuizAttempt;
use App\Models\CourseModule;
use App\Services\QuizService;
use Filament\Actions\Testing\TestAction;
use App\Filament\Resources\Courses\CourseResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\Courses\Pages\CourseAttempts;

/**
 * La solapa Intentos.
 *
 * Muestra **una fila por alumno y evaluación**, no una por intento: con veinte
 * alumnos y veinticinco clases, el registro plano enterraba lo único que hay que
 * ver, que es quién se quedó trabado.
 *
 * De ahí sale también la acción que destraba. Un alumno que agota los intentos
 * sin aprobar no puede seguir el curso —la progresión exige aprobar la
 * autoevaluación—, y hasta ahora el aula le decía «hablá con tu docente» sin que
 * el docente tuviera nada que hacer.
 */
class CourseAttemptsTest extends TestCase
{
    use RefreshDatabase;

    private QuizService $quizzes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());

        $this->quizzes = app(QuizService::class);
    }

    /** Un curso con una clase, su banco y su autoevaluación de tres intentos. */
    private function claseEvaluable(Course $course, int $intentos = 3): CourseClass
    {
        $module = CourseModule::factory()->for($course)->create([
            'order_number' => $course->modules()->max('order_number') + 1,
        ]);

        $class = CourseClass::factory()->for($module, 'module')->create(['order_number' => 1]);

        Question::factory()->count(3)->withOptions()->create(['class_id' => $class->id]);

        Quiz::factory()->create([
            'class_id' => $class->id,
            'questions_per_student' => 2,
            'passing_score' => 60,
            'max_attempts' => $intentos,
        ]);

        return $class->fresh();
    }

    /** Rinde respondiendo todo bien o todo mal. */
    private function rendir(CourseClass $class, Student $student, bool $bien): QuizAttempt
    {
        $attempt = $this->quizzes->start($class->quiz, $student);

        return $this->quizzes->submit($attempt, $attempt->questions->mapWithKeys(
            fn (Question $q): array => [$q->id => $q->options->firstWhere('is_correct', $bien)->id],
        )->all());
    }

    /** Agota los intentos sin aprobar: el alumno que queda trabado. */
    private function agotar(CourseClass $class, Student $student): QuizAttempt
    {
        $ultimo = null;

        foreach (range(1, $class->quiz->max_attempts) as $i) {
            $ultimo = $this->rendir($class, $student, false);
        }

        return $ultimo;
    }

    private function tabla(Course $course)
    {
        return Livewire::test(CourseAttempts::class, ['record' => $course->getKey()]);
    }

    public function test_la_solapa_abre(): void
    {
        $course = Course::factory()->create();

        $this->get(CourseResource::getUrl('attempts', ['record' => $course]))->assertSuccessful();
    }

    // ── Una fila por alumno y evaluación ────────────────────────────────────

    /** Tres intentos del mismo alumno son una sola fila, no tres. */
    public function test_los_intentos_de_un_alumno_se_agrupan_en_una_fila(): void
    {
        $course = Course::factory()->create();
        $class = $this->claseEvaluable($course);
        $student = Student::factory()->create();

        $primero = $this->rendir($class, $student, false);
        $segundo = $this->rendir($class, $student, false);

        $this->tabla($course)
            ->assertCanSeeTableRecords([$segundo])
            ->assertCanNotSeeTableRecords([$primero]);
    }

    public function test_la_fila_dice_cuantos_intentos_uso(): void
    {
        $course = Course::factory()->create();
        $class = $this->claseEvaluable($course);

        $this->rendir($class, Student::factory()->create(), false);

        $this->tabla($course)->assertSee('1 de 3');
    }

    public function test_muestra_a_cada_alumno_por_separado(): void
    {
        $course = Course::factory()->create();
        $class = $this->claseEvaluable($course);

        $uno = $this->rendir($class, Student::factory()->create(), false);
        $otro = $this->rendir($class, Student::factory()->create(), true);

        $this->tabla($course)->assertCanSeeTableRecords([$uno, $otro]);
    }

    public function test_no_lista_los_intentos_de_otro_curso(): void
    {
        $course = Course::factory()->create();
        $ajeno = $this->rendir($this->claseEvaluable(Course::factory()->create()), Student::factory()->create(), true);

        $this->tabla($course)->assertCanNotSeeTableRecords([$ajeno]);
    }

    public function test_incluye_los_examenes_de_modulo(): void
    {
        $course = Course::factory()->create();
        $class = $this->claseEvaluable($course);

        $examen = Quiz::factory()->create([
            'class_id' => null,
            'module_id' => $class->module_id,
            'questions_percentage' => 100,
        ]);

        $attempt = $this->quizzes->start($examen, Student::factory()->create());
        $this->quizzes->submit($attempt, []);

        $this->tabla($course)->assertCanSeeTableRecords([$attempt->fresh()]);
    }

    // ── Los tres estados ────────────────────────────────────────────────────

    public function test_el_que_aprobo_figura_como_aprobada(): void
    {
        $course = Course::factory()->create();
        $this->rendir($this->claseEvaluable($course), Student::factory()->create(), true);

        $this->tabla($course)->assertSee('Aprobada');
    }

    public function test_el_que_todavia_tiene_intentos_dice_cuantos_le_quedan(): void
    {
        $course = Course::factory()->create();
        $class = $this->claseEvaluable($course);
        $student = Student::factory()->create();

        $this->rendir($class, $student, false);
        $this->rendir($class, $student, false);

        $this->tabla($course)->assertSee('Le queda 1');
    }

    public function test_el_que_agoto_figura_sin_intentos(): void
    {
        $course = Course::factory()->create();
        $this->agotar($this->claseEvaluable($course), Student::factory()->create());

        $this->tabla($course)->assertSee('Sin intentos');
    }

    /** El filtro que convierte la pantalla en una lista de trabajo. */
    public function test_se_puede_filtrar_por_los_que_se_trabaron(): void
    {
        $course = Course::factory()->create();
        $class = $this->claseEvaluable($course);

        $trabado = $this->agotar($class, Student::factory()->create());
        $aprobado = $this->rendir($class, Student::factory()->create(), true);
        $conIntentos = $this->rendir($class, Student::factory()->create(), false);

        $this->tabla($course)
            ->filterTable('trabados', true)
            ->assertCanSeeTableRecords([$trabado])
            ->assertCanNotSeeTableRecords([$aprobado, $conIntentos]);
    }

    /**
     * El filtro por clase.
     *
     * Estaba escrito con el parámetro llamado `$q`, y Filament resuelve los
     * argumentos del closure **por nombre**: no filtraba nada y no había test
     * que lo notara.
     */
    public function test_se_puede_filtrar_por_clase(): void
    {
        $course = Course::factory()->create();
        $una = $this->claseEvaluable($course);
        $otra = $this->claseEvaluable($course);

        $deUna = $this->rendir($una, Student::factory()->create(), false);
        $deOtra = $this->rendir($otra, Student::factory()->create(), false);

        $this->tabla($course)
            ->filterTable('clase', $una->getKey())
            ->assertCanSeeTableRecords([$deUna])
            ->assertCanNotSeeTableRecords([$deOtra]);
    }

    // ── El historial ────────────────────────────────────────────────────────

    /**
     * El modal muestra **todos** los intentos, no sólo el último.
     *
     * Lo que se discute suele ser el recorrido: dos intentos con las mismas
     * preguntas mal, o una pregunta mal cargada.
     */
    public function test_el_historial_muestra_todos_los_intentos_con_sus_respuestas(): void
    {
        $course = Course::factory()->create();
        $class = $this->claseEvaluable($course);
        $student = Student::factory()->create();

        $this->rendir($class, $student, false);
        $ultimo = $this->rendir($class, $student, false);

        $pregunta = $ultimo->questions->first();

        $historial = $this->contenidoDelModal($course, $ultimo);

        $this->assertStringContainsString('Intento 1', $historial);
        $this->assertStringContainsString('Intento 2', $historial);
        $this->assertStringContainsString(e(strip_tags($pregunta->text)), $historial);
        $this->assertStringContainsString('Respondió:', $historial);
        $this->assertStringContainsString('Correcta:', $historial);
    }

    // ── Devolver los intentos ───────────────────────────────────────────────

    public function test_al_que_se_trabo_se_le_pueden_devolver_los_intentos(): void
    {
        $course = Course::factory()->create();
        $class = $this->claseEvaluable($course);
        $student = Student::factory()->create();

        $trabado = $this->agotar($class, $student);

        $this->tabla($course)->callAction(
            TestAction::make('resetear')->table($trabado),
            ['motivo' => 'Se le cortó internet en el segundo intento.'],
        );

        $this->assertSame(3, $this->quizzes->attemptsLeft($class->quiz, $student));
        $this->assertDatabaseHas('quiz_attempt_resets', [
            'quiz_id' => $class->quiz->getKey(),
            'student_id' => $student->getKey(),
            'reason' => 'Se le cortó internet en el segundo intento.',
        ]);
    }

    /** No hay nada que destrabar: aprobó. */
    public function test_no_se_ofrece_devolver_intentos_al_que_aprobo(): void
    {
        $course = Course::factory()->create();
        $aprobado = $this->rendir($this->claseEvaluable($course), Student::factory()->create(), true);

        $this->tabla($course)->assertActionHidden(TestAction::make('resetear')->table($aprobado));
    }

    /** Ni al que todavía puede rendir por su cuenta. */
    public function test_no_se_ofrece_devolver_intentos_al_que_todavia_tiene(): void
    {
        $course = Course::factory()->create();
        $attempt = $this->rendir($this->claseEvaluable($course), Student::factory()->create(), false);

        $this->tabla($course)->assertActionHidden(TestAction::make('resetear')->table($attempt));
    }

    /** Devueltos los intentos, la fila deja de figurar como trabada. */
    public function test_despues_de_devolverlos_la_fila_vuelve_a_tener_intentos(): void
    {
        $course = Course::factory()->create();
        $class = $this->claseEvaluable($course);
        $student = Student::factory()->create();

        $trabado = $this->agotar($class, $student);

        $this->tabla($course)->callAction(TestAction::make('resetear')->table($trabado), []);

        $this->tabla($course)->assertSee('0 de 3');
    }

    /**
     * El contenido del modal abierto sobre una fila.
     *
     * Livewire 4 no lo devuelve en el HTML de la página —el modal se pide recién
     * cuando se abre—, así que se renderiza la vista que quedó montada en la
     * acción. Eso igual comprueba lo que importa: que se arme con los intentos
     * del alumno correcto.
     */
    private function contenidoDelModal(Course $course, QuizAttempt $attempt): string
    {
        $component = $this->tabla($course)
            ->mountAction(TestAction::make('historial')->table($attempt));

        return (string) $component->instance()->getMountedAction()->getModalContent()->render();
    }
}
