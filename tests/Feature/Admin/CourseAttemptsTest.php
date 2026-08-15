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
 * La revisión de intentos.
 *
 * Existe para un caso concreto: un alumno reclama una nota y el docente tiene
 * que poder ver sobre qué preguntas se lo evaluó. Como el sorteo es por alumno,
 * sin esta pantalla el dato está guardado pero es inalcanzable.
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

    /** Un curso con una clase, su banco y su autoevaluación. */
    private function claseEvaluable(Course $course): CourseClass
    {
        $module = CourseModule::factory()->for($course)->create([
            'order_number' => $course->modules()->max('order_number') + 1,
        ]);

        $class = CourseClass::factory()->for($module, 'module')->create(['order_number' => 1]);

        Question::factory()->count(3)->withOptions()->create(['class_id' => $class->id]);
        Quiz::factory()->create(['class_id' => $class->id, 'questions_per_student' => 2, 'passing_score' => 60]);

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

    /**
     * El contenido del modal abierto sobre un intento.
     *
     * Livewire 4 no lo devuelve en el HTML de la página —el modal se pide recién
     * cuando se abre—, así que se renderiza la vista que quedó montada en la
     * acción. Eso igual comprueba lo que importa: que la acción arme el detalle
     * con el intento correcto.
     */
    private function detalleDe(Course $course, QuizAttempt $attempt): string
    {
        $component = Livewire::test(CourseAttempts::class, ['record' => $course->getKey()])
            ->mountAction(TestAction::make('respuestas')->table($attempt));

        return (string) $component->instance()->getMountedAction()->getModalContent()->render();
    }

    public function test_la_solapa_abre(): void
    {
        $course = Course::factory()->create();

        $this->get(CourseResource::getUrl('attempts', ['record' => $course]))->assertSuccessful();
    }

    public function test_lista_los_intentos_del_curso(): void
    {
        $course = Course::factory()->create();
        $class = $this->claseEvaluable($course);
        $attempt = $this->rendir($class, Student::factory()->create(), true);

        Livewire::test(CourseAttempts::class, ['record' => $course->getKey()])
            ->assertCanSeeTableRecords([$attempt]);
    }

    public function test_no_lista_los_intentos_de_otro_curso(): void
    {
        $course = Course::factory()->create();
        $ajeno = $this->rendir($this->claseEvaluable(Course::factory()->create()), Student::factory()->create(), true);

        Livewire::test(CourseAttempts::class, ['record' => $course->getKey()])
            ->assertCanNotSeeTableRecords([$ajeno]);
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

        $student = Student::factory()->create();
        $attempt = $this->quizzes->start($examen, $student);
        $this->quizzes->submit($attempt, []);

        Livewire::test(CourseAttempts::class, ['record' => $course->getKey()])
            ->assertCanSeeTableRecords([$attempt->fresh()]);
    }

    /** Lo que hace útil la pantalla: ver sobre qué se lo evaluó. */
    public function test_el_detalle_muestra_las_preguntas_y_lo_que_respondio(): void
    {
        $course = Course::factory()->create();
        $class = $this->claseEvaluable($course);
        $attempt = $this->rendir($class, Student::factory()->create(), false);

        $pregunta = $attempt->questions->first();

        $detalle = $this->detalleDe($course, $attempt);

        $this->assertStringContainsString(e(strip_tags($pregunta->text)), $detalle);
        $this->assertStringContainsString('Respondió:', $detalle);
        // Al haber respondido mal, tiene que verse cuál era la correcta
        $this->assertStringContainsString('Correcta:', $detalle);
        $this->assertStringContainsString(e($pregunta->correctOption()->option_text), $detalle);
    }

    public function test_se_puede_filtrar_por_resultado(): void
    {
        $course = Course::factory()->create();
        $class = $this->claseEvaluable($course);

        $aprobado = $this->rendir($class, Student::factory()->create(), true);
        $desaprobado = $this->rendir($class, Student::factory()->create(), false);

        Livewire::test(CourseAttempts::class, ['record' => $course->getKey()])
            ->filterTable('passed', true)
            ->assertCanSeeTableRecords([$aprobado])
            ->assertCanNotSeeTableRecords([$desaprobado]);
    }

    /** Un intento en curso todavía no tiene respuestas: no hay nada que mirar. */
    public function test_no_se_ofrece_el_detalle_de_un_intento_sin_entregar(): void
    {
        $course = Course::factory()->create();
        $class = $this->claseEvaluable($course);

        $enCurso = $this->quizzes->start($class->quiz, Student::factory()->create());

        Livewire::test(CourseAttempts::class, ['record' => $course->getKey()])
            ->assertActionHidden(TestAction::make('respuestas')->table($enCurso));
    }
}
