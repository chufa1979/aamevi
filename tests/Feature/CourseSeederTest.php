<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Quiz;
use App\Models\User;
use App\Models\Course;
use App\Models\Student;
use App\Models\Question;
use App\Models\CourseClass;
use App\Models\QuizAttempt;
use App\Models\ClassContent;
use App\Models\CourseModule;
use App\Enums\ClassContentType;
use App\Enums\EnrollmentStatus;
use App\Models\CourseEnrollment;
use App\Enums\ClassProgressState;
use App\Services\ProgressService;
use Database\Seeders\CourseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * El contenido de ejemplo no es relleno: es lo que se usa para revisar el panel
 * a ojo y para ver cómo se comporta con volumen real. Si deja de sembrar alguno
 * de los casos que ilustra —los cuatro tipos de material, los estados de
 * inscripción, los cinco estados de avance— deja de servir para eso sin que
 * nadie se entere.
 */
class CourseSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function alumnoDePrueba(): Student
    {
        return Student::find(User::where('email', 'alumno@aamevi.ar')->value('id'));
    }

    public function test_siembra_cinco_cursos_con_la_cantidad_de_modulos_pedida(): void
    {
        $this->assertSame(5, Course::count());

        $modulos = Course::withCount('modules')
            ->get()
            ->pluck('modules_count')
            ->sort()
            ->values()
            ->all();

        $this->assertSame([4, 5, 5, 6, 8], $modulos);
    }

    public function test_cada_modulo_tiene_al_menos_cinco_clases(): void
    {
        $flojos = CourseModule::has('classes', '<', 5)->get();

        $this->assertTrue($flojos->isEmpty(), 'Módulos con menos de cinco clases: '.$flojos->pluck('title')->implode(', '));
    }

    public function test_cada_clase_tiene_su_autoevaluacion_con_al_menos_cinco_preguntas(): void
    {
        $sinQuiz = CourseClass::doesntHave('quiz')->count();
        $this->assertSame(0, $sinQuiz, "{$sinQuiz} clases sin autoevaluación.");

        $pocasPreguntas = CourseClass::has('questions', '<', 5)->count();

        $this->assertSame(0, $pocasPreguntas, "{$pocasPreguntas} clases con menos de cinco preguntas.");
    }

    /** Ninguna pregunta puede repetirse: el examen de módulo junta los bancos de sus clases. */
    public function test_las_preguntas_no_se_repiten(): void
    {
        $textos = Question::pluck('text');

        $this->assertSame($textos->count(), $textos->unique()->count());
    }

    public function test_el_cronograma_va_de_julio_a_diciembre_de_2026(): void
    {
        $desde = CourseClass::min('activation_date');
        $hasta = CourseClass::max('activation_date');

        $this->assertStringStartsWith('2026-07', $desde);
        $this->assertStringStartsWith('2026-12', $hasta);
    }

    /** Con la fecha de hoy en el medio, cada curso queda partido en dictadas y por venir. */
    public function test_cada_curso_tiene_clases_dictadas_y_clases_por_venir(): void
    {
        foreach (Course::with('modules.classes')->get() as $course) {
            $clases = $course->modules->flatMap->classes;

            $this->assertGreaterThan(0, $clases->filter->isAvailable()->count(), "{$course->title} no tiene ninguna clase dictada.");
            $this->assertGreaterThan(0, $clases->reject->isAvailable()->count(), "{$course->title} no tiene ninguna clase por venir.");
        }
    }

    public function test_siembra_unos_veinte_alumnos_repartidos_en_los_cursos(): void
    {
        $this->assertGreaterThanOrEqual(20, Student::count());

        foreach (Course::all() as $course) {
            $this->assertGreaterThan(0, $course->enrollments()->count(), "{$course->title} sin inscriptos.");
        }
    }

    public function test_las_inscripciones_cubren_los_tres_estados(): void
    {
        $estados = CourseEnrollment::pluck('status');

        foreach ([EnrollmentStatus::Approved, EnrollmentStatus::Pending, EnrollmentStatus::Rejected] as $estado) {
            $this->assertContains($estado, $estados, "No hay ninguna inscripción {$estado->value}.");
        }
    }

    /** Lo que pidió el usuario: los alumnos rindieron las clases anteriores a hoy. */
    public function test_hay_intentos_rendidos_y_ninguno_sobre_una_clase_futura(): void
    {
        $this->assertGreaterThan(0, QuizAttempt::count(), 'Nadie rindió nada.');

        $futuros = QuizAttempt::with('quiz.class')
            ->get()
            ->filter(fn (QuizAttempt $a): bool => $a->quiz?->class !== null && ! $a->quiz->class->isAvailable())
            ->count();

        $this->assertSame(0, $futuros, 'Hay intentos sobre clases que todavía no se dictaron.');
    }

    public function test_los_examenes_de_modulo_sortean_un_porcentaje_del_banco(): void
    {
        $examenes = Quiz::whereNotNull('module_id')->get();

        $this->assertGreaterThan(0, $examenes->count());

        foreach ($examenes as $examen) {
            $this->assertNotNull($examen->questions_percentage);
            $this->assertTrue($examen->isReady(), 'Un examen de módulo quedó sin banco de preguntas.');
            $this->assertLessThanOrEqual($examen->poolSize(), $examen->questionsToDraw());
        }
    }

    /** El examen es opcional: si todos los módulos lo tuvieran, la solapa no mostraría la diferencia. */
    public function test_algunos_modulos_quedan_sin_examen(): void
    {
        $this->assertGreaterThan(0, CourseModule::doesntHave('quiz')->count());
        $this->assertGreaterThan(0, CourseModule::has('quiz')->count());
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
        foreach (ClassContent::where('type', ClassContentType::Video)->get() as $video) {
            $this->assertNotNull($video->embedUrl(), "El video «{$video->title}» no se puede incrustar.");
        }
    }

    /**
     * El seguimiento sólo sirve si los alumnos van a ritmos distintos. Con todos
     * iguales la grilla no muestra nada.
     */
    public function test_la_grilla_de_seguimiento_muestra_avances_distintos(): void
    {
        $progreso = app(ProgressService::class);
        $curso = Course::withCount('modules')->orderByDesc('modules_count')->first();

        $estados = collect($progreso->courseMatrix($curso))
            ->flatMap(fn (array $fila): array => array_values($fila))
            ->unique();

        $this->assertGreaterThanOrEqual(3, $estados->count(), 'La grilla tiene menos de tres estados distintos.');
        $this->assertTrue($estados->contains(ClassProgressState::Completed));
        $this->assertTrue($estados->contains(ClassProgressState::Scheduled));
    }

    public function test_es_idempotente(): void
    {
        $antes = [Course::count(), CourseClass::count(), ClassContent::count(), Quiz::count(), QuizAttempt::count()];

        $this->seed(CourseSeeder::class);

        $this->assertSame($antes, [Course::count(), CourseClass::count(), ClassContent::count(), Quiz::count(), QuizAttempt::count()]);
    }

    public function test_el_alumno_de_prueba_sigue_teniendo_sus_inscripciones(): void
    {
        $this->assertGreaterThan(0, $this->alumnoDePrueba()->enrollments()->count());
    }
}
