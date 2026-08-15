<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Student;
use App\Models\CourseClass;
use App\Models\CourseModule;
use App\Models\CourseEnrollment;
use App\Enums\ClassProgressState;
use App\Services\ProgressService;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\Courses\CourseResource;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CourseTrackingPageTest extends TestCase
{
    use RefreshDatabase;

    private ProgressService $progreso;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());

        $this->progreso = app(ProgressService::class);
    }

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

    public function test_la_pantalla_abre_y_muestra_a_los_inscriptos(): void
    {
        $course = $this->cursoCon(2);
        $student = $this->inscripto($course);

        $this->get(CourseResource::getUrl('tracking', ['record' => $course]))
            ->assertSuccessful()
            ->assertSee($student->user->full_name);
    }

    public function test_la_grilla_distingue_aprobada_de_bloqueada(): void
    {
        $course = $this->cursoCon(3);
        $student = $this->inscripto($course);
        [$primera, $segunda, $tercera] = $course->classes()->orderBy('order_number')->get()->all();

        $this->progreso->complete($student, $primera);
        $this->progreso->start($student, $segunda);

        $grilla = $this->progreso->courseMatrix($course->fresh());

        $this->assertSame(ClassProgressState::Completed, $grilla[$student->id][$primera->id]);
        $this->assertSame(ClassProgressState::InProgress, $grilla[$student->id][$segunda->id]);
        $this->assertSame(ClassProgressState::Locked, $grilla[$student->id][$tercera->id]);
    }

    public function test_una_clase_con_fecha_futura_figura_como_no_habilitada(): void
    {
        $course = $this->cursoCon(1);
        $student = $this->inscripto($course);

        $clase = $course->classes()->first();
        $clase->update(['activation_date' => now()->addWeek()]);

        $grilla = $this->progreso->courseMatrix($course->fresh());

        $this->assertSame(ClassProgressState::Scheduled, $grilla[$student->id][$clase->id]);
    }

    /**
     * La grilla reimplementa el gateo para poder resolverlo en memoria. Este
     * test es lo que impide que se desincronice de `canAccess()`, que es la
     * regla que realmente decide si el alumno entra.
     */
    public function test_la_grilla_no_contradice_a_can_access(): void
    {
        $course = $this->cursoCon(4);
        $student = $this->inscripto($course);
        $clases = $course->classes()->orderBy('order_number')->get();

        $this->progreso->complete($student, $clases[0]);
        $this->progreso->start($student, $clases[1]);
        $clases[3]->update(['activation_date' => now()->addWeek()]);

        $grilla = $this->progreso->courseMatrix($course->fresh());

        foreach ($course->fresh()->classes as $clase) {
            $this->assertSame(
                $this->progreso->canAccess($student, $clase),
                $grilla[$student->id][$clase->id]->isOpen(),
                "La grilla y canAccess() difieren en «{$clase->title}».",
            );
        }
    }

    public function test_los_no_inscriptos_no_aparecen(): void
    {
        $course = $this->cursoCon(1);
        $this->inscripto($course);
        $ajeno = Student::factory()->create();

        $grilla = $this->progreso->courseMatrix($course);

        $this->assertArrayNotHasKey($ajeno->id, $grilla);
    }

    /**
     * FID hace un SELECT por celda. Con 5 alumnos y 6 clases serían 90; acá
     * tienen que ser tres consultas, sin importar el tamaño del curso.
     */
    public function test_la_grilla_no_crece_en_consultas_con_el_tamano_del_curso(): void
    {
        $course = $this->cursoCon(6);

        foreach (range(1, 5) as $i) {
            $this->inscripto($course);
        }

        $course = $course->fresh();

        DB::enableQueryLog();
        $this->progreso->courseMatrix($course);
        $consultas = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(
            5,
            $consultas,
            "La grilla hizo {$consultas} consultas: está resolviendo celda por celda.",
        );
    }
}
