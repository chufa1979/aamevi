<?php

namespace Tests\Feature\Classroom;

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

/**
 * Cuándo se habilita el examen del módulo.
 *
 * El banco del examen combina las preguntas de todas las clases del módulo, así
 * que rendirlo antes de terminarlas sería preguntar sobre material que el alumno
 * todavía no vio.
 */
class ModuleExamAccessTest extends TestCase
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

    /** Un módulo con dos clases, cada una con preguntas y su autoevaluación. */
    private function moduloConDosClases(): CourseModule
    {
        $module = CourseModule::factory()->for(Course::factory())->create(['order_number' => 1]);

        foreach ([1, 2] as $orden) {
            $class = CourseClass::factory()->for($module, 'module')->create([
                'order_number' => $orden,
                'activation_date' => now()->subDay(),
            ]);

            Question::factory()->count(3)->withOptions()->create(['class_id' => $class->id]);
            Quiz::factory()->create(['class_id' => $class->id, 'questions_per_student' => 2]);
        }

        return $module->fresh();
    }

    private function inscripto(CourseModule $module): Student
    {
        $student = Student::factory()->create();

        CourseEnrollment::factory()->approved()->create([
            'course_id' => $module->course_id,
            'student_id' => $student->id,
        ]);

        return $student;
    }

    /** Aprueba la clase rindiendo su autoevaluación, como haría el alumno. */
    private function aprobar(Student $student, CourseClass $class): void
    {
        $attempt = $this->quizzes->start($class->quiz, $student);

        $this->quizzes->submit($attempt, $attempt->questions->mapWithKeys(
            fn (Question $q): array => [$q->id => $q->options->firstWhere('is_correct', true)->id],
        )->all());

        $this->progreso->complete($student, $class);
    }

    public function test_un_alumno_no_inscripto_no_puede_rendirlo(): void
    {
        $module = $this->moduloConDosClases();
        Quiz::factory()->create(['class_id' => null, 'module_id' => $module->id, 'questions_percentage' => 50]);

        $ajeno = Student::factory()->create();

        $this->assertFalse($this->progreso->canTakeModuleExam($ajeno, $module));
        $this->assertSame('No estás inscripto en este curso.', $this->progreso->moduleExamLockReason($ajeno, $module));
    }

    public function test_esta_cerrado_mientras_falten_clases(): void
    {
        $module = $this->moduloConDosClases();
        Quiz::factory()->create(['class_id' => null, 'module_id' => $module->id, 'questions_percentage' => 50]);

        $student = $this->inscripto($module);

        $this->assertFalse($this->progreso->canTakeModuleExam($student, $module));
        $this->assertSame('Te faltan aprobar 2 clases del módulo.', $this->progreso->moduleExamLockReason($student, $module));

        $this->aprobar($student, $module->classes()->orderBy('order_number')->first());

        // El mensaje se ajusta al singular cuando queda una sola
        $this->assertSame('Te falta aprobar una clase del módulo.', $this->progreso->moduleExamLockReason($student, $module));
    }

    public function test_se_habilita_al_aprobar_todas_las_clases(): void
    {
        $module = $this->moduloConDosClases();
        Quiz::factory()->create(['class_id' => null, 'module_id' => $module->id, 'questions_percentage' => 50]);

        $student = $this->inscripto($module);

        foreach ($module->classes()->orderBy('order_number')->get() as $class) {
            $this->aprobar($student, $class);
        }

        $this->assertTrue($this->progreso->canTakeModuleExam($student, $module));
        $this->assertNull($this->progreso->moduleExamLockReason($student, $module));
    }

    /** El examen es opcional: un módulo sin examen no bloquea nada, pero tampoco se puede rendir. */
    public function test_un_modulo_sin_examen_lo_dice(): void
    {
        $module = $this->moduloConDosClases();
        $student = $this->inscripto($module);

        $this->assertSame('Este módulo no tiene examen.', $this->progreso->moduleExamLockReason($student, $module));
    }

    /** Un examen sin banco se aprobaría solo: no se puede abrir. */
    public function test_un_examen_sin_preguntas_no_se_puede_rendir(): void
    {
        $module = CourseModule::factory()->for(Course::factory())->create(['order_number' => 1]);
        Quiz::factory()->create(['class_id' => null, 'module_id' => $module->id, 'questions_percentage' => 50]);

        $student = $this->inscripto($module);

        $this->assertFalse($this->progreso->canTakeModuleExam($student, $module));
        $this->assertSame('El examen todavía no tiene preguntas cargadas.', $this->progreso->moduleExamLockReason($student, $module));
    }
}
