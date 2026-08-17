<?php

namespace Tests\Feature\Teacher;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\CourseEnrollment;
use App\Services\SupportService;
use App\Filament\Resources\Courses\CourseResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\Courses\Pages\CourseTickets;

/**
 * El aviso de consultas pendientes en el panel.
 *
 * Una consulta sin contestar es lo único del panel que tiene a alguien
 * esperando del otro lado: cargar material o corregir lo maneja el docente a su
 * ritmo. Lo que protegen estos tests es que el número sea **suyo** —no el de
 * todos los cursos— y que baje al responder.
 */
class PendingTicketsTest extends TestCase
{
    use RefreshDatabase;

    private SupportService $consultas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consultas = app(SupportService::class);
    }

    /** Un curso con una consulta esperando respuesta. */
    private function cursoConConsulta(?Teacher $teacher = null, string $asunto = 'Una duda'): Course
    {
        $course = Course::factory()->for($teacher ?? Teacher::factory())->create();
        $student = Student::factory()->create();

        CourseEnrollment::factory()->approved()->create([
            'course_id' => $course->getKey(),
            'student_id' => $student->getKey(),
        ]);

        $this->consultas->open($student, $course, $asunto, 'No entiendo la consigna.');

        return $course->fresh();
    }

    public function test_el_docente_cuenta_solo_las_de_sus_cursos(): void
    {
        $teacher = Teacher::factory()->create();

        $this->cursoConConsulta($teacher);
        $this->cursoConConsulta($teacher);
        $this->cursoConConsulta(); // de otro docente

        $this->assertSame(2, $this->consultas->pendingFor($teacher->user));
    }

    public function test_el_administrador_las_cuenta_todas(): void
    {
        $this->cursoConConsulta();
        $this->cursoConConsulta();

        $this->assertSame(2, $this->consultas->pendingFor(User::factory()->admin()->create()));
    }

    /** Responder saca la consulta de la lista de pendientes. */
    public function test_al_responder_deja_de_contar(): void
    {
        $teacher = Teacher::factory()->create();
        $course = $this->cursoConConsulta($teacher);

        $this->consultas->reply($course->tickets()->firstOrFail(), $teacher->user, 'Ya te contesto.');

        $this->assertSame(0, $this->consultas->pendingFor($teacher->user));
    }

    public function test_una_cerrada_tampoco_cuenta(): void
    {
        $teacher = Teacher::factory()->create();
        $course = $this->cursoConConsulta($teacher);

        $this->consultas->close($course->tickets()->firstOrFail());

        $this->assertSame(0, $this->consultas->pendingFor($teacher->user));
    }

    // ── En el panel ─────────────────────────────────────────────────────────

    public function test_el_menu_lateral_muestra_el_numero(): void
    {
        $teacher = Teacher::factory()->create();
        $this->cursoConConsulta($teacher);
        $this->cursoConConsulta($teacher);

        $this->actingAs($teacher->user);

        $this->assertSame('2', CourseResource::getNavigationBadge());
    }

    /** Sin pendientes no se muestra nada: un cero permanente enseña a ignorarlo. */
    public function test_sin_pendientes_no_hay_numero(): void
    {
        $this->actingAs(Teacher::factory()->create()->user);

        $this->assertNull(CourseResource::getNavigationBadge());
    }

    public function test_la_solapa_del_curso_muestra_las_de_ese_curso(): void
    {
        $teacher = Teacher::factory()->create();
        $conConsulta = $this->cursoConConsulta($teacher);
        $this->cursoConConsulta($teacher); // otro curso, otra consulta

        $this->actingAs($teacher->user);

        // El badge de la solapa sale del curso de la ruta, no del total
        $this->get("/profesores/courses/{$conConsulta->getKey()}/consultas")->assertSuccessful();

        $this->assertSame('1', CourseTickets::getNavigationBadge());
    }

    public function test_el_listado_de_cursos_marca_cual_tiene_consultas(): void
    {
        $teacher = Teacher::factory()->create();
        $this->cursoConConsulta($teacher, 'Consulta pendiente');

        $this->actingAs($teacher->user)
            ->get('/profesores/courses')
            ->assertSuccessful()
            ->assertSee('Consultas');
    }
}
