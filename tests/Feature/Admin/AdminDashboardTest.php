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
use App\Models\CourseModule;
use App\Models\SupportTicket;
use App\Services\QuizService;
use Filament\Facades\Filament;
use App\Filament\Widgets\StuckStudents;
use App\Filament\Widgets\PendingSupportTickets;
use App\Filament\Widgets\CoursesWithoutActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * El escritorio del administrador.
 *
 * Junta en un solo lugar lo que cada pantalla ya avisa por separado —
 * inscripciones pendientes, alumnos trabados, consultas sin responder— más una
 * señal que hasta ahora no existía en ningún lado: qué curso activo lleva
 * treinta días sin que el docente le toque nada.
 */
class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());

        Filament::setCurrentPanel('admin');
    }

    public function test_el_escritorio_abre(): void
    {
        $this->get('/admin')->assertSuccessful();
    }

    private function claseEvaluable(Course $course): CourseClass
    {
        $module = CourseModule::factory()->for($course)->create();
        $class = CourseClass::factory()->for($module, 'module')->create(['order_number' => 1]);

        Question::factory()->count(3)->withOptions()->create(['class_id' => $class->id]);

        Quiz::factory()->create([
            'class_id' => $class->id,
            'questions_per_student' => 2,
            'passing_score' => 60,
            'max_attempts' => 1,
        ]);

        return $class->fresh();
    }

    public function test_muestra_a_los_alumnos_trabados_de_cualquier_curso(): void
    {
        $course = Course::factory()->create();
        $class = $this->claseEvaluable($course);
        $student = Student::factory()->create();

        $quizzes = app(QuizService::class);
        $attempt = $quizzes->start($class->quiz, $student);
        $quizzes->submit($attempt, $attempt->questions->mapWithKeys(
            fn (Question $q): array => [$q->id => $q->options->firstWhere('is_correct', false)->id],
        )->all());

        Livewire::test(StuckStudents::class)
            ->assertSee($student->user->full_name)
            ->assertSee($course->title);
    }

    public function test_muestra_las_consultas_sin_responder_de_cualquier_curso(): void
    {
        $course = Course::factory()->create();
        $student = Student::factory()->create();

        $ticket = SupportTicket::factory()->for($course)->for($student)->create();

        Livewire::test(PendingSupportTickets::class)
            ->assertSee($student->user->full_name)
            ->assertSee($course->title)
            ->assertSee($ticket->subject);
    }

    public function test_no_muestra_una_consulta_ya_respondida(): void
    {
        $course = Course::factory()->create();
        $student = Student::factory()->create();

        SupportTicket::factory()->for($course)->for($student)->answered()->create(['subject' => 'Ya respondida']);

        Livewire::test(PendingSupportTickets::class)->assertDontSee('Ya respondida');
    }

    public function test_un_curso_activo_recien_creado_no_figura_sin_actividad(): void
    {
        $course = Course::factory()->create(['is_active' => true]);
        $this->claseEvaluable($course);

        // Recién creado: sus módulos y clases se tocaron ahora mismo.
        Livewire::test(CoursesWithoutActivity::class)->assertDontSee($course->title);
    }

    /** `update()` respeta `$fillable`, que no incluye `updated_at`: hay que tocarlo por fuera. */
    private function envejecer(CourseModule $module, int $dias): void
    {
        $module->timestamps = false;
        $module->forceFill(['updated_at' => now()->subDays($dias)])->save();
    }

    public function test_un_curso_activo_sin_tocar_hace_mas_de_treinta_dias_figura(): void
    {
        $course = Course::factory()->create(['is_active' => true]);
        $this->envejecer(CourseModule::factory()->for($course)->create(), 45);

        Livewire::test(CoursesWithoutActivity::class)->assertSee($course->title);
    }

    public function test_un_curso_inactivo_no_figura_aunque_este_sin_tocar(): void
    {
        $course = Course::factory()->create(['is_active' => false]);
        $this->envejecer(CourseModule::factory()->for($course)->create(), 45);

        Livewire::test(CoursesWithoutActivity::class)->assertDontSee($course->title);
    }
}
