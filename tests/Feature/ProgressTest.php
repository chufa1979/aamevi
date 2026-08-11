<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Quiz;
use App\Models\Course;
use App\Models\Student;
use App\Models\Question;
use App\Models\CourseClass;
use App\Models\CourseModule;
use App\Services\QuizService;
use App\Models\CourseEnrollment;
use App\Services\ProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProgressTest extends TestCase
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

    /** Un curso con un módulo de N clases ya habilitadas. */
    private function cursoCon(int $clases): Course
    {
        $course = Course::factory()->create();
        $module = CourseModule::factory()->for($course)->create(['order_number' => 1]);

        foreach (range(1, $clases) as $i) {
            CourseClass::factory()->for($module, 'module')->create([
                'order_number' => $i,
                'activation_date' => now()->subDay(),
            ]);
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

    public function test_un_alumno_sin_inscripcion_no_entra(): void
    {
        $course = $this->cursoCon(2);
        $ajeno = Student::factory()->create();
        $primera = $course->classes()->orderBy('order_number')->first();

        $this->assertFalse($this->progreso->canAccess($ajeno, $primera));
        $this->assertSame('No estás inscripto en este curso.', $this->progreso->lockReason($ajeno, $primera));
    }

    public function test_una_inscripcion_pendiente_no_alcanza(): void
    {
        $course = $this->cursoCon(1);
        $student = Student::factory()->create();

        CourseEnrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);

        $this->assertFalse($this->progreso->isEnrolled($student, $course));
    }

    public function test_la_primera_clase_esta_abierta_para_un_inscripto(): void
    {
        $course = $this->cursoCon(3);
        $student = $this->inscripto($course);
        $primera = $course->classes()->orderBy('order_number')->first();

        $this->assertTrue($this->progreso->canAccess($student, $primera));
        $this->assertNull($this->progreso->lockReason($student, $primera));
    }

    public function test_la_segunda_clase_esta_cerrada_hasta_aprobar_la_primera(): void
    {
        $course = $this->cursoCon(3);
        $student = $this->inscripto($course);
        [$primera, $segunda] = $course->classes()->orderBy('order_number')->get()->all();

        $this->assertFalse($this->progreso->canAccess($student, $segunda));
        $this->assertSame('Primero tenés que aprobar la clase anterior.', $this->progreso->lockReason($student, $segunda));

        $this->progreso->complete($student, $primera);

        $this->assertTrue($this->progreso->canAccess($student, $segunda->fresh()));
    }

    public function test_una_clase_con_fecha_futura_esta_cerrada(): void
    {
        $course = $this->cursoCon(1);
        $student = $this->inscripto($course);

        $clase = $course->classes()->first();
        $clase->update(['activation_date' => now()->addWeek()]);

        $this->assertFalse($this->progreso->canAccess($student, $clase->fresh()));
        $this->assertStringContainsString('Se habilita el', $this->progreso->lockReason($student, $clase->fresh()));
    }

    /** Marcar completa una clase con quiz sin aprobarlo saltearía la evaluación. */
    public function test_no_se_completa_una_clase_con_quiz_sin_aprobarlo(): void
    {
        $course = $this->cursoCon(2);
        $student = $this->inscripto($course);
        $primera = $course->classes()->orderBy('order_number')->first();

        Question::factory()->count(2)->withOptions()->create(['class_id' => $primera->id]);
        Quiz::factory()->create(['class_id' => $primera->id, 'questions_per_student' => 2]);

        $this->assertFalse($this->progreso->complete($student, $primera->fresh()));
        $this->assertFalse($this->progreso->hasCompleted($student, $primera));
    }

    public function test_aprobar_el_quiz_habilita_la_clase_siguiente(): void
    {
        $course = $this->cursoCon(2);
        $student = $this->inscripto($course);
        [$primera, $segunda] = $course->classes()->orderBy('order_number')->get()->all();

        Question::factory()->count(2)->withOptions()->create(['class_id' => $primera->id]);
        $quiz = Quiz::factory()->create(['class_id' => $primera->id, 'questions_per_student' => 2]);

        $attempt = $this->quizzes->start($quiz, $student);
        $this->quizzes->submit($attempt, $attempt->questions->mapWithKeys(fn (Question $q): array => [
            $q->id => $q->options->firstWhere('is_correct', true)->id,
        ])->all());

        $this->assertTrue($this->progreso->complete($student, $primera->fresh()));
        $this->assertTrue($this->progreso->canAccess($student, $segunda));
    }

    /** El gateo es por módulo: encadenar todo el curso trabaría al alumno. */
    public function test_la_primera_clase_de_cada_modulo_esta_abierta(): void
    {
        $course = Course::factory()->create();
        $student = $this->inscripto($course);

        foreach ([1, 2] as $orden) {
            $module = CourseModule::factory()->for($course)->create(['order_number' => $orden]);
            CourseClass::factory()->for($module, 'module')->create([
                'order_number' => 1,
                'activation_date' => now()->subDay(),
            ]);
        }

        foreach ($course->fresh()->classes as $clase) {
            $this->assertTrue($this->progreso->canAccess($student, $clase), 'Cada módulo abre en su primera clase.');
        }
    }

    public function test_el_avance_del_curso_es_el_porcentaje_de_clases_completadas(): void
    {
        $course = $this->cursoCon(4);
        $student = $this->inscripto($course);
        $clases = $course->classes()->orderBy('order_number')->get();

        $this->assertSame(0, $this->progreso->courseProgress($student, $course));

        $this->progreso->complete($student, $clases[0]);
        $this->assertSame(25, $this->progreso->courseProgress($student, $course));

        $this->progreso->complete($student, $clases[1]);
        $this->assertSame(50, $this->progreso->courseProgress($student, $course));
    }

    public function test_abrir_una_clase_dos_veces_no_duplica_el_avance(): void
    {
        $course = $this->cursoCon(1);
        $student = $this->inscripto($course);
        $clase = $course->classes()->first();

        $this->progreso->start($student, $clase);
        $this->progreso->start($student, $clase);

        $this->assertDatabaseCount('student_progress', 1);
    }
}
