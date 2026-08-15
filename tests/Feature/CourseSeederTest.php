<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Quiz;
use App\Models\User;
use App\Models\Course;
use App\Models\Student;
use App\Models\CourseClass;
use App\Models\ClassContent;
use App\Enums\ClassContentType;
use App\Enums\EnrollmentStatus;
use App\Services\ProgressService;
use Database\Seeders\CourseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * El contenido de ejemplo no es solo relleno: es lo que se usa para revisar el
 * panel y el aula a ojo. Si deja de sembrar alguno de los casos que ilustra
 * —los cuatro tipos de material, los estados de inscripción, los dos motivos de
 * bloqueo— deja de servir para eso sin que nadie se entere.
 */
class CourseSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function alumno(): Student
    {
        return Student::find(User::where('email', 'alumno@aamevi.ar')->value('id'));
    }

    public function test_siembra_los_tres_cursos_con_su_arbol(): void
    {
        $this->assertSame(3, Course::count());

        foreach (Course::all() as $course) {
            $this->assertGreaterThan(0, $course->modules()->count(), "{$course->title} sin módulos.");
            $this->assertGreaterThan(0, $course->classes()->count(), "{$course->title} sin clases.");
        }
    }

    public function test_es_idempotente(): void
    {
        $antes = [Course::count(), CourseClass::count(), ClassContent::count(), Quiz::count()];

        $this->seed(CourseSeeder::class);

        $this->assertSame($antes, [Course::count(), CourseClass::count(), ClassContent::count(), Quiz::count()]);
    }

    public function test_siembra_los_cuatro_tipos_de_material(): void
    {
        foreach (ClassContentType::cases() as $tipo) {
            $this->assertTrue(
                ClassContent::where('type', $tipo)->exists(),
                "No hay ningún material de tipo {$tipo->value}.",
            );
        }
    }

    public function test_los_videos_de_ejemplo_se_pueden_incrustar(): void
    {
        $videos = ClassContent::where('type', ClassContentType::Video)->get();

        $this->assertNotEmpty($videos);

        foreach ($videos as $video) {
            $this->assertNotNull($video->embedUrl(), "El video «{$video->title}» no se puede incrustar.");
        }
    }

    public function test_hay_una_clase_en_vivo_con_enlace(): void
    {
        $vivo = CourseClass::where('is_live_session', true)->first();

        $this->assertNotNull($vivo, 'Ninguna clase en vivo sembrada.');
        $this->assertNotNull($vivo->meet_link);
    }

    public function test_los_examenes_de_modulo_sortean_un_porcentaje_del_banco(): void
    {
        $examenes = Quiz::whereNotNull('module_id')->get();

        $this->assertNotEmpty($examenes);

        foreach ($examenes as $examen) {
            $this->assertNotNull($examen->questions_percentage);
            $this->assertTrue($examen->isReady(), 'Un examen de módulo quedó sin banco de preguntas.');
            $this->assertLessThanOrEqual($examen->poolSize(), $examen->questionsToDraw());
        }
    }

    public function test_los_quiz_de_clase_tienen_preguntas_con_una_sola_correcta(): void
    {
        $quizzes = Quiz::whereNotNull('class_id')->get();

        $this->assertNotEmpty($quizzes);

        foreach ($quizzes as $quiz) {
            $this->assertTrue($quiz->isReady(), "El quiz «{$quiz->title}» no tiene preguntas.");

            foreach ($quiz->questionPool()->with('options')->get() as $pregunta) {
                $this->assertSame(
                    1,
                    $pregunta->options->where('is_correct', true)->count(),
                    'Toda pregunta tiene exactamente una opción correcta.',
                );
            }
        }
    }

    public function test_el_alumno_queda_con_una_inscripcion_aprobada_y_una_pendiente(): void
    {
        $inscripciones = $this->alumno()->enrollments()->pluck('status');

        $this->assertContains(EnrollmentStatus::Approved, $inscripciones);
        $this->assertContains(EnrollmentStatus::Pending, $inscripciones);
    }

    /** El ejemplo tiene que mostrar los dos candados, no solo uno. */
    public function test_ilustra_el_bloqueo_por_fecha_y_por_clase_anterior(): void
    {
        $progreso = app(ProgressService::class);
        $alumno = $this->alumno();

        $motivos = CourseClass::all()
            ->map(fn (CourseClass $clase): ?string => $progreso->lockReason($alumno, $clase))
            ->filter()
            ->values();

        $this->assertTrue(
            $motivos->contains(fn (string $m): bool => str_contains($m, 'Se habilita el')),
            'Ninguna clase con fecha futura visible para el alumno inscripto.',
        );

        $this->assertTrue(
            $motivos->contains('Primero tenés que aprobar la clase anterior.'),
            'Ninguna clase bloqueada por la anterior.',
        );
    }

    public function test_la_primera_clase_del_curso_aprobado_esta_abierta(): void
    {
        $progreso = app(ProgressService::class);

        $curso = $this->alumno()->enrollments()
            ->where('status', EnrollmentStatus::Approved)
            ->first()
            ->course;

        $primera = $curso->modules()->first()->classes()->orderBy('order_number')->first();

        $this->assertNull($progreso->lockReason($this->alumno(), $primera));
    }
}
