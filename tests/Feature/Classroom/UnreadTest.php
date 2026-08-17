<?php

namespace Tests\Feature\Classroom;

use Tests\TestCase;
use App\Models\Course;
use App\Models\Student;
use App\Models\Announcement;
use App\Models\CourseEnrollment;
use App\Services\SupportService;
use App\Services\AnnouncementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * El contador de sin leer del menú del aula.
 *
 * Lo que hay que proteger es que **baje**: un contador que se queda pegado en
 * dos es peor que no tenerlo, porque enseña a ignorarlo. Y que no cuente lo
 * propio: el mensaje que escribió el alumno no es una novedad para él.
 */
class UnreadTest extends TestCase
{
    use RefreshDatabase;

    private AnnouncementService $comunicaciones;

    private SupportService $consultas;

    private Student $student;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->comunicaciones = app(AnnouncementService::class);
        $this->consultas = app(SupportService::class);
        $this->student = Student::factory()->create();
        $this->course = Course::factory()->create(['title' => 'Nutrición aplicada']);

        CourseEnrollment::factory()->approved()->create([
            'course_id' => $this->course->getKey(),
            'student_id' => $this->student->getKey(),
        ]);
    }

    private function comunicar(array $atributos = []): Announcement
    {
        return Announcement::factory()->create([
            'course_id' => $this->course->getKey(),
            ...$atributos,
        ]);
    }

    // ── Comunicaciones ──────────────────────────────────────────────────────

    public function test_una_comunicacion_nueva_cuenta_como_sin_leer(): void
    {
        $this->comunicar();

        $this->assertSame(1, $this->comunicaciones->unreadFor($this->student, $this->course));
    }

    public function test_abrir_el_tablon_las_da_por_leidas(): void
    {
        $this->comunicar();
        $this->comunicar(['title' => 'Otra más']);

        $this->actingAs($this->student->user)
            ->get(route('classroom.announcements', $this->course))
            ->assertSuccessful();

        $this->assertSame(0, $this->comunicaciones->unreadFor($this->student, $this->course));
    }

    /** Estando en el tablón el contador sobra: las está mirando. */
    public function test_en_el_tablon_el_contador_ya_no_aparece(): void
    {
        $this->comunicar();

        $this->actingAs($this->student->user)
            ->get(route('classroom.announcements', $this->course))
            ->assertSuccessful()
            ->assertDontSee('sin leer');
    }

    /** Pero sí aparece en cualquier otra pantalla del aula. */
    public function test_el_menu_avisa_desde_otra_pantalla_del_curso(): void
    {
        $this->comunicar();

        $this->actingAs($this->student->user)
            ->get(route('classroom.course', $this->course))
            ->assertSuccessful()
            ->assertSee('1 sin leer');
    }

    public function test_una_publicada_despues_vuelve_a_contar(): void
    {
        $this->comunicar();
        $this->comunicaciones->markRead($this->student, $this->comunicaciones->forStudent($this->student, $this->course));

        $this->comunicar(['title' => 'Novedad']);

        $this->assertSame(1, $this->comunicaciones->unreadFor($this->student, $this->course));
    }

    /** La dirigida a otro alumno no es un pendiente suyo. */
    public function test_no_cuenta_la_dirigida_a_otro(): void
    {
        $otro = Student::factory()->create();
        CourseEnrollment::factory()->approved()->create([
            'course_id' => $this->course->getKey(),
            'student_id' => $otro->getKey(),
        ]);

        $this->comunicar(['student_id' => $otro->getKey()]);

        $this->assertSame(0, $this->comunicaciones->unreadFor($this->student, $this->course));
        $this->assertSame(1, $this->comunicaciones->unreadFor($otro, $this->course));
    }

    public function test_no_cuenta_la_de_un_curso_que_no_cursa(): void
    {
        Announcement::factory()->create(['course_id' => Course::factory()->create()->getKey()]);

        $this->assertSame(0, $this->comunicaciones->unreadFor($this->student));
    }

    // ── Consultas ───────────────────────────────────────────────────────────

    public function test_la_propia_consulta_no_cuenta_como_sin_leer(): void
    {
        $this->consultas->open($this->student, $this->course, 'Duda', 'No me abre el video.');

        $this->assertSame(0, $this->consultas->unreadFor($this->student));
    }

    public function test_la_respuesta_del_docente_cuenta(): void
    {
        $ticket = $this->consultas->open($this->student, $this->course, 'Duda', 'No me abre el video.');

        $this->consultas->reply($ticket, $this->course->teacher->user, 'Ya lo vemos.');

        $this->assertSame(1, $this->consultas->unreadFor($this->student));
    }

    public function test_abrir_el_hilo_lo_da_por_leido(): void
    {
        $ticket = $this->consultas->open($this->student, $this->course, 'Duda', 'No me abre el video.');
        $this->consultas->reply($ticket, $this->course->teacher->user, 'Ya lo vemos.');

        $this->actingAs($this->student->user)
            ->get(route('classroom.ticket', $ticket))
            ->assertSuccessful();

        $this->assertSame(0, $this->consultas->unreadFor($this->student));
    }

    /**
     * El hilo sigue: una respuesta posterior vuelve a marcarla.
     *
     * El salto en el tiempo no es decorativo. Lo que compara `unreadFor()` es la
     * fecha del mensaje contra la de lectura, y en un test las dos caen en el
     * mismo segundo; sin avanzar el reloj, la respuesta nueva parecería anterior
     * a la lectura.
     */
    public function test_una_respuesta_nueva_la_marca_otra_vez(): void
    {
        $ticket = $this->consultas->open($this->student, $this->course, 'Duda', 'No me abre el video.');
        $this->consultas->reply($ticket, $this->course->teacher->user, 'Ya lo vemos.');

        $this->actingAs($this->student->user)->get(route('classroom.ticket', $ticket));

        $this->travel(1)->minute();

        $this->consultas->reply($ticket->fresh(), $this->course->teacher->user, 'Resuelto.');

        $this->assertSame(1, $this->consultas->unreadFor($this->student));
    }

    // ── El menú ─────────────────────────────────────────────────────────────

    public function test_el_menu_muestra_el_numero(): void
    {
        $ticket = $this->consultas->open($this->student, $this->course, 'Duda', 'No me abre el video.');
        $this->consultas->reply($ticket, $this->course->teacher->user, 'Ya lo vemos.');

        $this->actingAs($this->student->user)
            ->get('/mis-cursos')
            ->assertSuccessful()
            ->assertSee('1 sin leer');
    }

    /** Sin pendientes no se muestra nada: un cero permanente es ruido. */
    public function test_sin_pendientes_el_menu_no_muestra_nada(): void
    {
        $this->actingAs($this->student->user)
            ->get('/mis-cursos')
            ->assertSuccessful()
            ->assertDontSee('sin leer');
    }
}
