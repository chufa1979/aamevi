<?php

namespace Tests\Feature\Classroom;

use Tests\TestCase;
use App\Models\Quiz;
use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Question;
use App\Models\Certificate;
use App\Models\CourseClass;
use App\Models\ClassContent;
use App\Models\CourseModule;
use App\Services\QuizService;
use App\Enums\EnrollmentStatus;
use App\Models\CourseEnrollment;
use App\Services\ProgressService;
use Illuminate\Http\UploadedFile;
use App\Services\SubmissionService;
use App\Services\CertificateService;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\CertificateException;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * El certificado de finalización.
 *
 * Es la afirmación institucional de que alguien aprobó un curso, así que lo que
 * se prueba acá es sobre todo cuándo **no** se emite: con una clase sin
 * completar, con una tarea esperando corrección, o con la corrección hecha pero
 * sin publicar. Y que se emita una sola vez.
 */
class CertificateTest extends TestCase
{
    use RefreshDatabase;

    private CertificateService $certificados;

    private ProgressService $progreso;

    private SubmissionService $entregas;

    private QuizService $quizzes;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->certificados = app(CertificateService::class);
        $this->progreso = app(ProgressService::class);
        $this->entregas = app(SubmissionService::class);
        $this->quizzes = app(QuizService::class);
    }

    /**
     * Un curso de `$clases` clases sin evaluación ni tareas, con el alumno
     * inscripto y aprobado.
     *
     * @return array{0: Course, 1: Student}
     */
    private function cursoCon(int $clases = 2): array
    {
        $course = Course::factory()->create(['title' => 'Medicina del estilo de vida']);
        $module = CourseModule::factory()->for($course)->create(['order_number' => 1]);

        for ($i = 1; $i <= $clases; $i++) {
            CourseClass::factory()->for($module, 'module')->create([
                'order_number' => $i,
                'activation_date' => now()->subDays(30 - $i),
            ]);
        }

        $student = Student::factory()->create();

        CourseEnrollment::factory()->approved()->create([
            'course_id' => $course->getKey(),
            'student_id' => $student->getKey(),
        ]);

        return [$course->fresh(), $student];
    }

    /** Completa todas las clases del curso. */
    private function cursarTodo(Student $student, Course $course): void
    {
        foreach ($course->classes()->orderBy('order_number')->get() as $class) {
            $this->progreso->complete($student, $class);
        }
    }

    // ── Cuándo se emite ─────────────────────────────────────────────────────

    public function test_completar_la_ultima_clase_emite_el_certificado(): void
    {
        [$course, $student] = $this->cursoCon(2);

        $this->cursarTodo($student, $course);

        $certificado = $this->certificados->of($student, $course);

        $this->assertNotNull($certificado);
        $this->assertStringStartsWith('AAMEVI-'.now()->year.'-', $certificado->certificate_number);
    }

    public function test_no_se_emite_con_una_clase_sin_completar(): void
    {
        [$course, $student] = $this->cursoCon(3);

        $primera = $course->classes()->orderBy('order_number')->first();
        $this->progreso->complete($student, $primera);

        $this->assertNull($this->certificados->of($student, $course));
        $this->assertStringContainsString('clases', $this->certificados->blocker($student, $course));
    }

    /** La clase se completa al aprobar su evaluación: ése es el otro camino. */
    public function test_aprobar_la_evaluacion_de_la_ultima_clase_tambien_lo_emite(): void
    {
        [$course, $student] = $this->cursoCon(1);

        $class = $course->classes()->first();
        Question::factory()->count(3)->withOptions()->create(['class_id' => $class->getKey()]);
        $quiz = Quiz::factory()->create([
            'class_id' => $class->getKey(),
            'questions_per_student' => 2,
            'passing_score' => 50,
        ]);

        $attempt = $this->quizzes->start($quiz, $student);
        $this->quizzes->submit($attempt, $attempt->questions->mapWithKeys(
            fn (Question $q): array => [$q->id => $q->options->firstWhere('is_correct', true)->id],
        )->all());

        $this->progreso->complete($student, $class->fresh());

        $this->assertNotNull($this->certificados->of($student, $course));
    }

    // ── Las tareas ──────────────────────────────────────────────────────────

    /**
     * Para pasar de clase alcanza con entregar; para el certificado no. La
     * diferencia es deliberada: el certificado afirma que aprobó.
     */
    public function test_no_se_emite_con_una_tarea_esperando_correccion(): void
    {
        [$course, $student] = $this->cursoCon(1);

        $class = $course->classes()->first();
        $tarea = ClassContent::factory()->task()->for($class, 'class')->create(['title' => 'Trabajo final']);

        $this->entregas->submit($student, $tarea, UploadedFile::fake()->create('tp.pdf', 20, 'application/pdf'));
        $this->progreso->complete($student, $class);

        $this->assertNull($this->certificados->of($student, $course));
        $this->assertStringContainsString('Trabajo final', $this->certificados->blocker($student, $course));
    }

    /** Corregida y sin publicar el alumno no sabe nada: emitirlo delataría la nota. */
    public function test_no_se_emite_con_la_correccion_sin_publicar(): void
    {
        [$course, $student] = $this->cursoCon(1);

        $class = $course->classes()->first();
        $tarea = ClassContent::factory()->task()->for($class, 'class')->create();

        $entrega = $this->entregas->submit($student, $tarea, UploadedFile::fake()->create('tp.pdf', 20, 'application/pdf'));
        $this->progreso->complete($student, $class);
        $this->entregas->grade($entrega, Teacher::factory()->create(), 9, true, 'Muy bien');

        $this->assertNull($this->certificados->of($student, $course));
    }

    public function test_publicar_la_ultima_correccion_lo_emite(): void
    {
        [$course, $student] = $this->cursoCon(1);

        $class = $course->classes()->first();
        $tarea = ClassContent::factory()->task()->for($class, 'class')->create();

        $entrega = $this->entregas->submit($student, $tarea, UploadedFile::fake()->create('tp.pdf', 20, 'application/pdf'));
        $this->progreso->complete($student, $class);
        $entrega = $this->entregas->grade($entrega, Teacher::factory()->create(), 9, true, 'Muy bien');

        $this->assertNull($this->certificados->of($student, $course));

        $this->entregas->publish($entrega);

        $this->assertNotNull($this->certificados->of($student, $course));
    }

    public function test_no_se_emite_con_una_tarea_desaprobada(): void
    {
        [$course, $student] = $this->cursoCon(1);

        $class = $course->classes()->first();
        $tarea = ClassContent::factory()->task()->for($class, 'class')->create();

        $entrega = $this->entregas->submit($student, $tarea, UploadedFile::fake()->create('tp.pdf', 20, 'application/pdf'));
        $this->progreso->complete($student, $class);
        $entrega = $this->entregas->grade($entrega, Teacher::factory()->create(), 3, false, 'Rehacelo');
        $this->entregas->publish($entrega);

        $this->assertNull($this->certificados->of($student, $course));
    }

    // ── Emitir una sola vez ─────────────────────────────────────────────────

    public function test_no_se_emite_dos_veces(): void
    {
        [$course, $student] = $this->cursoCon(1);

        $this->cursarTodo($student, $course);
        $primero = $this->certificados->of($student, $course);

        // Volver a completar la misma clase dispara el evento de nuevo
        $this->cursarTodo($student, $course);

        $this->assertSame(1, Certificate::query()->ofStudent($student)->count());
        $this->assertSame($primero->getKey(), $this->certificados->of($student, $course)->getKey());
    }

    public function test_la_emision_manual_falla_si_ya_tiene_certificado(): void
    {
        [$course, $student] = $this->cursoCon(1);

        $this->cursarTodo($student, $course);
        $enrollment = CourseEnrollment::where('course_id', $course->getKey())->firstOrFail();

        $this->expectException(CertificateException::class);

        $this->certificados->issue($enrollment);
    }

    /** La escapatoria de §3-E: el docente puede darlo por aprobado igual. */
    public function test_la_emision_manual_no_espera_a_que_termine(): void
    {
        [$course, $student] = $this->cursoCon(3);

        $enrollment = CourseEnrollment::where('course_id', $course->getKey())->firstOrFail();

        $certificado = $this->certificados->issue($enrollment);

        $this->assertNotNull($certificado);
        $this->assertNotNull($this->certificados->blocker($student, $course));
    }

    // ── La inscripción ──────────────────────────────────────────────────────

    /** Es el único lugar del sistema que lleva una inscripción a «finalizada». */
    public function test_emitir_deja_la_inscripcion_finalizada(): void
    {
        [$course, $student] = $this->cursoCon(1);

        $this->cursarTodo($student, $course);

        $enrollment = CourseEnrollment::where('course_id', $course->getKey())->firstOrFail();

        $this->assertSame(EnrollmentStatus::Completed, $enrollment->status);
    }

    public function test_al_alumno_no_inscripto_no_se_le_emite_nada(): void
    {
        $course = Course::factory()->create();
        CourseModule::factory()->for($course)->create(['order_number' => 1]);

        $ajeno = Student::factory()->create();

        $this->assertNull($this->certificados->issueIfEarned($ajeno, $course));
        $this->assertSame('No estás inscripto en este curso.', $this->certificados->blocker($ajeno, $course));
    }

    // ── Las pantallas ───────────────────────────────────────────────────────

    public function test_el_alumno_ve_su_certificado_y_lo_que_le_falta(): void
    {
        [$course, $student] = $this->cursoCon(1);
        $this->cursarTodo($student, $course);

        [$otro] = $this->cursoCon(3);
        CourseEnrollment::factory()->approved()->create([
            'course_id' => $otro->getKey(),
            'student_id' => $student->getKey(),
        ]);

        $numero = $this->certificados->of($student, $course)->certificate_number;

        $this->actingAs($student->user)
            ->get('/certificados')
            ->assertSuccessful()
            ->assertSee($numero)
            ->assertSee('Medicina del estilo de vida')
            ->assertSee('En camino');
    }

    public function test_se_descarga_el_pdf(): void
    {
        [$course, $student] = $this->cursoCon(1);
        $this->cursarTodo($student, $course);

        $certificado = $this->certificados->of($student, $course);

        $response = $this->actingAs($student->user)
            ->get(route('classroom.certificate', $certificado));

        $response->assertSuccessful();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    /** El link de un compañero no sirve. */
    public function test_no_se_descarga_el_certificado_de_otro(): void
    {
        [$course, $student] = $this->cursoCon(1);
        $this->cursarTodo($student, $course);

        $certificado = $this->certificados->of($student, $course);
        $otro = Student::factory()->create();

        $this->actingAs($otro->user)
            ->get(route('classroom.certificate', $certificado))
            ->assertNotFound();
    }
}
