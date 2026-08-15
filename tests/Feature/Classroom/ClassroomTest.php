<?php

namespace Tests\Feature\Classroom;

use Tests\TestCase;
use App\Models\Quiz;
use App\Models\User;
use App\Models\Course;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\Question;
use App\Models\CourseClass;
use App\Models\CourseModule;
use App\Services\QuizService;
use App\Models\CourseEnrollment;
use App\Services\ProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * El aula del alumno, de punta a punta.
 *
 * Lo que más importa acá no es que las pantallas abran sino que **no** abran
 * cuando no corresponde: el gateo tiene que valer también para quien escribe la
 * URL a mano, no sólo para quien hace clic en los enlaces.
 */
class ClassroomTest extends TestCase
{
    use RefreshDatabase;

    private ProgressService $progreso;

    private QuizService $quizzes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->progreso = app(ProgressService::class);
        $this->quizzes = app(QuizService::class);
    }

    /** Un curso de un módulo con `$clases` clases ya habilitadas, cada una con su autoevaluación. */
    private function curso(int $clases = 3): Course
    {
        $course = Course::factory()->create();
        $module = CourseModule::factory()->for($course)->create(['order_number' => 1]);

        foreach (range(1, $clases) as $i) {
            $class = CourseClass::factory()->for($module, 'module')->create([
                'order_number' => $i,
                'activation_date' => now()->subDays($clases - $i + 1),
            ]);

            Question::factory()->count(3)->withOptions()->create(['class_id' => $class->id]);
            Quiz::factory()->create(['class_id' => $class->id, 'questions_per_student' => 2, 'passing_score' => 60]);
        }

        return $course->fresh();
    }

    private function inscripto(Course $course): Student
    {
        $student = Student::factory()->create();

        CourseEnrollment::factory()->approved()->create([
            'course_id' => $course->id,
            'student_id' => $student->id,
        ]);

        return $student;
    }

    private function entrar(Student $student): self
    {
        $this->actingAs($student->user);

        return $this;
    }

    // ── Quién entra al aula ─────────────────────────────────────────────────

    public function test_un_invitado_no_entra(): void
    {
        $this->get(route('classroom.courses'))->assertRedirect('/login');
    }

    /** El aula trabaja sobre la ficha de alumno: un administrador no la tiene. */
    public function test_un_administrador_no_entra_al_aula(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('classroom.courses'))
            ->assertForbidden();
    }

    public function test_un_profesor_tampoco(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Teacher]))
            ->get(route('classroom.courses'))
            ->assertForbidden();
    }

    // ── Mis cursos y progreso ───────────────────────────────────────────────

    public function test_mis_cursos_muestra_los_cursos_del_alumno(): void
    {
        $course = $this->curso();
        $student = $this->inscripto($course);

        $this->entrar($student)
            ->get(route('classroom.courses'))
            ->assertSuccessful()
            ->assertSee($course->title);
    }

    public function test_mis_cursos_no_muestra_cursos_ajenos(): void
    {
        $propio = $this->curso();
        $ajeno = Course::factory()->create(['title' => 'Curso de otra persona']);
        $student = $this->inscripto($propio);

        $this->entrar($student)
            ->get(route('classroom.courses'))
            ->assertSuccessful()
            ->assertDontSee($ajeno->title);
    }

    public function test_el_progreso_abre_y_muestra_el_porcentaje(): void
    {
        $course = $this->curso();
        $student = $this->inscripto($course);

        $this->entrar($student)
            ->get(route('classroom.progress'))
            ->assertSuccessful()
            ->assertSee('0%');
    }

    // ── El temario ──────────────────────────────────────────────────────────

    public function test_el_temario_lista_todas_las_clases_con_su_estado(): void
    {
        $course = $this->curso();
        $student = $this->inscripto($course);
        $clases = $course->classes()->orderBy('order_number')->get();

        $respuesta = $this->entrar($student)->get(route('classroom.course', $course))->assertSuccessful();

        // A diferencia del aula de referencia, las bloqueadas se listan igual
        foreach ($clases as $class) {
            $respuesta->assertSee($class->title);
        }

        $respuesta->assertSee('Disponible')
            ->assertSee('Bloqueada')
            ->assertSee('Primero tenés que aprobar la clase anterior.');
    }

    public function test_no_se_puede_ver_el_temario_de_un_curso_ajeno(): void
    {
        $ajeno = $this->curso();
        $student = $this->inscripto($this->curso());

        $this->entrar($student)
            ->get(route('classroom.course', $ajeno))
            ->assertForbidden();
    }

    // ── El aula de la clase ─────────────────────────────────────────────────

    public function test_la_primera_clase_abre_y_muestra_su_material(): void
    {
        $course = $this->curso();
        $student = $this->inscripto($course);
        $primera = $course->classes()->orderBy('order_number')->first();

        $this->entrar($student)
            ->get(route('classroom.class', $primera))
            ->assertSuccessful()
            ->assertSee($primera->title);
    }

    /** El gateo vale aunque el alumno escriba la URL: es lo que protege la progresión. */
    public function test_escribir_la_url_de_una_clase_bloqueada_da_403(): void
    {
        $course = $this->curso();
        $student = $this->inscripto($course);
        $segunda = $course->classes()->orderBy('order_number')->get()[1];

        $this->entrar($student)
            ->get(route('classroom.class', $segunda))
            ->assertForbidden();
    }

    public function test_una_clase_de_un_curso_ajeno_da_403(): void
    {
        $ajeno = $this->curso();
        $student = $this->inscripto($this->curso());

        $this->entrar($student)
            ->get(route('classroom.class', $ajeno->classes()->first()))
            ->assertForbidden();
    }

    public function test_abrir_la_clase_la_marca_como_empezada(): void
    {
        $course = $this->curso();
        $student = $this->inscripto($course);
        $primera = $course->classes()->orderBy('order_number')->first();

        $this->entrar($student)->get(route('classroom.class', $primera));

        $this->assertDatabaseHas('student_progress', [
            'student_id' => $student->id,
            'class_id' => $primera->id,
        ]);
    }

    /** Una clase con evaluación no se puede dar por vista salteándola. */
    public function test_no_se_puede_marcar_vista_una_clase_con_evaluacion(): void
    {
        $course = $this->curso();
        $student = $this->inscripto($course);
        $primera = $course->classes()->orderBy('order_number')->first();

        $this->entrar($student)
            ->post(route('classroom.class.complete', $primera))
            ->assertRedirect();

        $this->assertFalse($this->progreso->hasCompleted($student, $primera));
    }

    public function test_una_clase_sin_evaluacion_se_marca_vista(): void
    {
        $course = Course::factory()->create();
        $module = CourseModule::factory()->for($course)->create(['order_number' => 1]);
        $class = CourseClass::factory()->for($module, 'module')->create([
            'order_number' => 1,
            'activation_date' => now()->subDay(),
        ]);

        $student = $this->inscripto($course);

        $this->entrar($student)
            ->post(route('classroom.class.complete', $class))
            ->assertRedirect();

        $this->assertTrue($this->progreso->hasCompleted($student, $class));
    }

    // ── Rendir ──────────────────────────────────────────────────────────────

    public function test_la_evaluacion_abre_con_sus_preguntas(): void
    {
        $course = $this->curso();
        $student = $this->inscripto($course);
        $quiz = $course->classes()->orderBy('order_number')->first()->quiz;

        $this->entrar($student)
            ->get(route('classroom.quiz', $quiz))
            ->assertSuccessful()
            ->assertSee('Entregar');
    }

    /**
     * Lo que no se copia del aula de referencia: allá la respuesta correcta
     * viajaba al navegador en un campo oculto y se veía con «ver código fuente».
     */
    public function test_la_pantalla_no_revela_cual_es_la_respuesta_correcta(): void
    {
        $course = $this->curso();
        $student = $this->inscripto($course);
        $class = $course->classes()->orderBy('order_number')->first();

        $html = $this->entrar($student)->get(route('classroom.quiz', $class->quiz))->getContent();

        foreach ($class->questions as $question) {
            $correcta = $question->correctOption();

            // El id de la correcta aparece —es el value del radio— pero nada
            // debe decir cuál lo es
            $this->assertStringNotContainsString('is_correct', $html);
            $this->assertStringNotContainsString('respuestacorrecta', $html);
            $this->assertStringNotContainsString('correcta="'.$correcta->id.'"', $html);
        }
    }

    public function test_aprobar_la_evaluacion_completa_la_clase_y_habilita_la_siguiente(): void
    {
        $course = $this->curso();
        $student = $this->inscripto($course);
        [$primera, $segunda] = $course->classes()->orderBy('order_number')->get()->all();

        $this->entrar($student)->get(route('classroom.quiz', $primera->quiz));

        $attempt = $this->quizzes->attemptsOf($primera->quiz, $student)->last();

        $respuestas = $attempt->questions->mapWithKeys(fn (Question $q): array => [
            $q->id => $q->options->firstWhere('is_correct', true)->id,
        ])->all();

        $this->post(route('classroom.quiz.submit', $primera->quiz), ['respuestas' => $respuestas])
            ->assertRedirect(route('classroom.quiz', $primera->quiz));

        $this->assertTrue($this->progreso->hasCompleted($student, $primera->fresh()));

        // Y ahora la siguiente abre
        $this->get(route('classroom.class', $segunda))->assertSuccessful();
    }

    public function test_el_resultado_muestra_la_nota_y_la_devolucion(): void
    {
        $course = $this->curso();
        $student = $this->inscripto($course);
        $quiz = $course->classes()->orderBy('order_number')->first()->quiz;

        $this->entrar($student)->get(route('classroom.quiz', $quiz));

        $attempt = $this->quizzes->attemptsOf($quiz, $student)->last();

        // Todas bien menos la primera: así se ven los dos lados de la devolución
        $respuestas = $attempt->questions->mapWithKeys(fn (Question $q, int $i): array => [
            $q->id => $i === 0
                ? $q->options->firstWhere('is_correct', false)->id
                : $q->options->firstWhere('is_correct', true)->id,
        ])->all();

        $this->followingRedirects()
            ->post(route('classroom.quiz.submit', $quiz), ['respuestas' => $respuestas])
            ->assertSuccessful()
            ->assertSee('Respondiste:')
            // La correcta se revela recién acá, después de entregar
            ->assertSee('Correcta:')
            ->assertSee($attempt->questions->first()->correctOption()->option_text);
    }

    /** Recargar la pantalla de resultado no puede consumir otro intento. */
    public function test_volver_al_resultado_no_abre_un_intento_nuevo(): void
    {
        $course = $this->curso();
        $student = $this->inscripto($course);
        $quiz = $course->classes()->orderBy('order_number')->first()->quiz;

        $this->entrar($student)->get(route('classroom.quiz', $quiz));

        $attempt = $this->quizzes->attemptsOf($quiz, $student)->last();

        $respuestas = $attempt->questions->mapWithKeys(fn (Question $q): array => [
            $q->id => $q->options->firstWhere('is_correct', true)->id,
        ])->all();

        $this->post(route('classroom.quiz.submit', $quiz), ['respuestas' => $respuestas]);
        $this->get(route('classroom.quiz', $quiz))->assertSuccessful();

        $this->assertCount(1, $this->quizzes->attemptsOf($quiz, $student));
    }

    /** Las que no se responden se registran igual, para distinguirlas de las no preguntadas. */
    public function test_entregar_sin_responder_registra_las_respuestas_vacias(): void
    {
        $course = $this->curso();
        $student = $this->inscripto($course);
        $quiz = $course->classes()->orderBy('order_number')->first()->quiz;

        $this->entrar($student)->get(route('classroom.quiz', $quiz));

        $attempt = $this->quizzes->attemptsOf($quiz, $student)->last();

        $this->post(route('classroom.quiz.submit', $quiz), ['respuestas' => []]);

        $attempt->refresh();

        $this->assertSame(0, $attempt->score);
        $this->assertFalse($attempt->passed);
        $this->assertCount($attempt->questions->count(), $attempt->answers);
    }

    public function test_no_se_puede_rendir_la_evaluacion_de_una_clase_bloqueada(): void
    {
        $course = $this->curso();
        $student = $this->inscripto($course);
        $segunda = $course->classes()->orderBy('order_number')->get()[1];

        $this->entrar($student)
            ->get(route('classroom.quiz', $segunda->quiz))
            ->assertForbidden();
    }

    // ── Catálogo ────────────────────────────────────────────────────────────

    public function test_el_catalogo_muestra_los_cursos_disponibles(): void
    {
        $disponible = Course::factory()->create(['title' => 'Curso abierto']);
        $student = Student::factory()->create();

        $this->entrar($student)
            ->get(route('classroom.catalog'))
            ->assertSuccessful()
            ->assertSee('Curso abierto');
    }

    public function test_solicitar_inscripcion_deja_la_solicitud_pendiente(): void
    {
        $course = Course::factory()->create();
        $student = Student::factory()->create();

        $this->entrar($student)
            ->post(route('classroom.enroll', $course))
            ->assertRedirect(route('classroom.courses'));

        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $course->id,
            'student_id' => $student->id,
            'status' => 'pending',
        ]);
    }

    public function test_no_se_puede_solicitar_dos_veces(): void
    {
        $course = Course::factory()->create();
        $student = Student::factory()->create();

        $this->entrar($student)->post(route('classroom.enroll', $course));

        $this->post(route('classroom.enroll', $course))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('course_enrollments', 1);
    }

    // ── Evaluaciones del curso ──────────────────────────────────────────────

    public function test_la_pantalla_de_evaluaciones_lista_las_del_curso(): void
    {
        $course = $this->curso();
        $student = $this->inscripto($course);

        $this->entrar($student)
            ->get(route('classroom.evaluations', $course))
            ->assertSuccessful()
            ->assertSee('Sin rendir');
    }
}
