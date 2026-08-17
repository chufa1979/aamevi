<?php

namespace Tests\Feature\Classroom;

use Tests\TestCase;
use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use App\Enums\EmailType;
use App\Models\QueuedEmail;
use App\Models\Announcement;
use App\Models\CourseEnrollment;
use App\Services\AnnouncementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * El tablón de comunicaciones del curso.
 *
 * Lo que fija el comportamiento es que **publicar y avisar son dos cosas**: la
 * comunicación queda siempre en el tablón, y el correo sale sólo si el docente
 * lo pide. En FID el módulo se llamaba comunicaciones y no mandaba un solo mail;
 * acá el correo existe pero no es obligatorio.
 */
class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    private AnnouncementService $comunicaciones;

    private Course $course;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->comunicaciones = app(AnnouncementService::class);
        $this->course = Course::factory()->create(['title' => 'Nutrición aplicada']);
        $this->student = $this->inscribir();
    }

    private function inscribir(): Student
    {
        $student = Student::factory()->create();

        CourseEnrollment::factory()->approved()->create([
            'course_id' => $this->course->getKey(),
            'student_id' => $student->getKey(),
        ]);

        return $student;
    }

    private function comunicar(array $atributos = []): Announcement
    {
        return Announcement::factory()->create([
            'course_id' => $this->course->getKey(),
            ...$atributos,
        ]);
    }

    // ── Quién la ve ─────────────────────────────────────────────────────────

    public function test_el_alumno_ve_las_del_curso(): void
    {
        $this->comunicar(['title' => 'Se corre la clase del jueves']);

        $this->actingAs($this->student->user)
            ->get(route('classroom.announcements', $this->course))
            ->assertSuccessful()
            ->assertSee('Se corre la clase del jueves');
    }

    /** Una dirigida a otro alumno no es asunto suyo. */
    public function test_no_ve_la_dirigida_a_otro(): void
    {
        $otro = $this->inscribir();

        $this->comunicar(['title' => 'Sobre tu entrega', 'student_id' => $otro->getKey()]);

        $this->actingAs($this->student->user)
            ->get(route('classroom.announcements', $this->course))
            ->assertSuccessful()
            ->assertDontSee('Sobre tu entrega');
    }

    public function test_si_ve_la_dirigida_a_el(): void
    {
        $this->comunicar(['title' => 'Sobre tu entrega', 'student_id' => $this->student->getKey()]);

        $this->actingAs($this->student->user)
            ->get(route('classroom.announcements', $this->course))
            ->assertSee('Sobre tu entrega');
    }

    public function test_quien_no_cursa_no_entra_al_tablon(): void
    {
        $ajeno = Student::factory()->create();

        $this->actingAs($ajeno->user)
            ->get(route('classroom.announcements', $this->course))
            ->assertForbidden();
    }

    // ── El aviso por correo ─────────────────────────────────────────────────

    public function test_publicar_no_manda_nada_por_si_solo(): void
    {
        $this->comunicar();

        $this->assertSame(0, QueuedEmail::count());
    }

    public function test_avisar_encola_uno_por_alumno_del_curso(): void
    {
        $this->inscribir();
        $comunicacion = $this->comunicar();

        $encolados = $this->comunicaciones->notify($comunicacion);

        $this->assertSame(2, $encolados);
        $this->assertSame(2, QueuedEmail::where('email_type', EmailType::Announcement)->count());
        $this->assertNotNull($comunicacion->fresh()->notified_at);
    }

    public function test_la_dirigida_a_uno_solo_le_llega_a_el(): void
    {
        $this->inscribir();
        $comunicacion = $this->comunicar(['student_id' => $this->student->getKey()]);

        $this->assertSame(1, $this->comunicaciones->notify($comunicacion));
        $this->assertSame($this->student->getKey(), QueuedEmail::firstOrFail()->recipient_id);
    }

    /** Corregir una errata no puede volver a llenarle la casilla a nadie. */
    public function test_avisar_dos_veces_no_encola_de_nuevo(): void
    {
        $comunicacion = $this->comunicar();

        $this->comunicaciones->notify($comunicacion);
        $this->assertSame(0, $this->comunicaciones->notify($comunicacion->fresh()));

        $this->assertSame(1, QueuedEmail::count());
    }

    public function test_el_correo_lleva_el_texto_de_la_comunicacion(): void
    {
        $comunicacion = $this->comunicar([
            'title' => 'Cambio de aula',
            'body' => '<p>Nos mudamos al <strong>aula 3</strong>.</p>',
        ]);

        $this->comunicaciones->notify($comunicacion);

        $aviso = QueuedEmail::firstOrFail();

        $this->assertSame('Cambio de aula', $aviso->subject);
        $this->assertStringContainsString('<strong>aula 3</strong>', $aviso->body);
    }

    // ── El panel ────────────────────────────────────────────────────────────

    public function test_el_docente_ve_la_solapa_de_su_curso(): void
    {
        $this->comunicar(['title' => 'Aviso del curso']);

        $this->actingAs($this->course->teacher->user)
            ->get("/profesores/courses/{$this->course->getKey()}/comunicaciones")
            ->assertSuccessful()
            ->assertSee('Aviso del curso');
    }

    public function test_un_docente_ajeno_no_llega(): void
    {
        $this->actingAs(Teacher::factory()->create()->user)
            ->get("/profesores/courses/{$this->course->getKey()}/comunicaciones")
            ->assertNotFound();
    }
}
