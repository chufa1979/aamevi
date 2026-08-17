<?php

namespace Tests\Feature\Classroom;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use Livewire\Livewire;
use App\Models\Student;
use App\Models\Teacher;
use App\Enums\EmailType;
use App\Enums\TicketStatus;
use App\Models\QueuedEmail;
use App\Models\SupportTicket;
use Filament\Facades\Filament;
use App\Models\CourseEnrollment;
use App\Services\SupportService;
use App\Exceptions\SupportException;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\Courses\Pages\CourseTickets;

/**
 * Consultas a mesa de ayuda.
 *
 * Tres cosas de FID se corrigen acá y son las que protegen estos tests: el hilo
 * admite varios mensajes, la consulta cuelga del curso —y la atiende quien lo
 * dicta—, y el aviso va por la cola en lugar de a una casilla quemada en el
 * código.
 */
class SupportTest extends TestCase
{
    use RefreshDatabase;

    private SupportService $consultas;

    private Student $student;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consultas = app(SupportService::class);
        $this->student = Student::factory()->create();
        $this->course = Course::factory()->create(['title' => 'Nutrición aplicada']);

        CourseEnrollment::factory()->approved()->create([
            'course_id' => $this->course->getKey(),
            'student_id' => $this->student->getKey(),
        ]);
    }

    private function abrir(string $asunto = 'No me abre el video'): SupportTicket
    {
        return $this->consultas->open($this->student, $this->course, $asunto, 'Probé desde dos navegadores.');
    }

    private function docente(): User
    {
        return $this->course->teacher->user;
    }

    // ── Abrir ───────────────────────────────────────────────────────────────

    public function test_abrir_una_consulta_deja_su_primer_mensaje(): void
    {
        $ticket = $this->abrir();

        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertSame(1, $ticket->messages()->count());
        $this->assertSame($this->student->getKey(), $ticket->question()->author_id);
    }

    /** Consultar sobre un curso que no cursa no tiene sentido: la atiende su docente. */
    public function test_no_se_puede_consultar_sobre_un_curso_ajeno(): void
    {
        $this->expectException(SupportException::class);

        $this->consultas->open($this->student, Course::factory()->create(), 'Hola', 'Consulta');
    }

    public function test_no_se_abre_una_consulta_vacia(): void
    {
        $this->expectException(SupportException::class);

        $this->consultas->open($this->student, $this->course, 'Asunto', '   ');
    }

    // ── El hilo ─────────────────────────────────────────────────────────────

    /** Lo que FID no tenía: en un hilo de una sola respuesta no se puede repreguntar. */
    public function test_el_hilo_admite_varios_mensajes(): void
    {
        $ticket = $this->abrir();

        $this->consultas->reply($ticket, $this->docente(), 'Probá con otro navegador.');
        $this->consultas->reply($ticket->fresh(), $this->student->user, 'Sigue igual.');
        $this->consultas->reply($ticket->fresh(), $this->docente(), 'Lo revisamos.');

        $this->assertSame(4, $ticket->messages()->count());
    }

    public function test_el_estado_sale_de_quien_escribio_ultimo(): void
    {
        $ticket = $this->abrir();

        $this->consultas->reply($ticket, $this->docente(), 'Ya lo vemos.');
        $this->assertSame(TicketStatus::Answered, $ticket->fresh()->status);

        $this->consultas->reply($ticket->fresh(), $this->student->user, 'Gracias, pero sigue.');
        $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);
    }

    public function test_la_respuesta_del_docente_encola_un_aviso(): void
    {
        $ticket = $this->abrir();

        $this->consultas->reply($ticket, $this->docente(), 'Ya está resuelto.');

        $aviso = QueuedEmail::where('email_type', EmailType::SupportReply)->firstOrFail();

        $this->assertSame($this->student->getKey(), $aviso->recipient_id);
        $this->assertStringContainsString('No me abre el video', $aviso->subject);
    }

    /** Al alumno se le avisa; el docente ve el pendiente en su panel. */
    public function test_el_mensaje_del_alumno_no_encola_nada(): void
    {
        $ticket = $this->abrir();
        $this->consultas->reply($ticket, $this->docente(), 'Contame más.');

        QueuedEmail::query()->delete();

        $this->consultas->reply($ticket->fresh(), $this->student->user, 'Pasa en Chrome.');

        $this->assertSame(0, QueuedEmail::count());
    }

    // ── Cerrar ──────────────────────────────────────────────────────────────

    public function test_una_consulta_cerrada_no_recibe_mensajes(): void
    {
        $ticket = $this->consultas->close($this->abrir());

        $this->expectException(SupportException::class);

        $this->consultas->reply($ticket, $this->student->user, 'Una más.');
    }

    // ── Quién la ve ─────────────────────────────────────────────────────────

    public function test_la_atiende_el_docente_del_curso_y_el_administrador(): void
    {
        $ticket = $this->abrir();

        $this->assertTrue($this->consultas->canHandle($this->docente(), $ticket));
        $this->assertTrue($this->consultas->canHandle(User::factory()->admin()->create(), $ticket));
        $this->assertFalse($this->consultas->canHandle(Teacher::factory()->create()->user, $ticket));
    }

    // ── Las pantallas ───────────────────────────────────────────────────────

    public function test_el_alumno_abre_una_consulta_desde_el_aula(): void
    {
        $this->actingAs($this->student->user)->post('/consultas', [
            'course_id' => $this->course->getKey(),
            'subject' => 'Duda con la entrega',
            'message' => '¿La entrego en PDF?',
        ])->assertRedirect();

        $this->assertDatabaseHas('support_tickets', ['subject' => 'Duda con la entrega']);
    }

    public function test_no_puede_consultar_sobre_un_curso_que_no_cursa(): void
    {
        $ajeno = Course::factory()->create();

        $this->actingAs($this->student->user)->post('/consultas', [
            'course_id' => $ajeno->getKey(),
            'subject' => 'Hola',
            'message' => 'Consulta',
        ])->assertSessionHasErrors('course_id');

        $this->assertSame(0, SupportTicket::count());
    }

    public function test_el_listado_muestra_las_suyas(): void
    {
        $this->abrir('Mi consulta');

        $this->actingAs($this->student->user)
            ->get('/consultas')
            ->assertSuccessful()
            ->assertSee('Mi consulta');
    }

    /** El enlace de otro no abre nada. */
    public function test_no_se_ve_la_consulta_de_otro(): void
    {
        $ticket = $this->abrir();
        $otro = Student::factory()->create();

        $this->actingAs($otro->user)
            ->get(route('classroom.ticket', $ticket))
            ->assertNotFound();
    }

    public function test_el_alumno_puede_cerrar_la_suya(): void
    {
        $ticket = $this->abrir();

        $this->actingAs($this->student->user)
            ->post(route('classroom.ticket.close', $ticket))
            ->assertRedirect();

        $this->assertTrue($ticket->fresh()->isClosed());
    }

    public function test_el_docente_ve_las_consultas_de_su_curso_en_el_panel(): void
    {
        $this->abrir('Consulta visible');

        $this->actingAs($this->docente())
            ->get("/profesores/courses/{$this->course->getKey()}/consultas")
            ->assertSuccessful()
            ->assertSee('Consulta visible');
    }

    /**
     * Abrir el hilo desde el panel.
     *
     * Va con Livewire y no con un GET porque el modal es una acción: los tests
     * que sólo pedían la pantalla daban verde con el botón roto.
     */
    public function test_el_docente_abre_el_hilo_desde_el_panel(): void
    {
        $ticket = $this->abrir('Consulta abierta');

        $this->actingAs($this->docente());
        Filament::setCurrentPanel('profesores');

        Livewire::test(CourseTickets::class, ['record' => $this->course->getKey()])
            ->mountAction(TestAction::make('hilo')->table($ticket))
            ->assertHasNoErrors();
    }

    /** Y también el de una cerrada, que no lleva botón de enviar. */
    public function test_el_docente_abre_el_hilo_de_una_cerrada(): void
    {
        $ticket = $this->consultas->close($this->abrir('Consulta cerrada'));

        $this->actingAs($this->docente());
        Filament::setCurrentPanel('profesores');

        Livewire::test(CourseTickets::class, ['record' => $this->course->getKey()])
            ->mountAction(TestAction::make('hilo')->table($ticket))
            ->assertHasNoErrors();
    }

    public function test_el_docente_responde_desde_el_panel(): void
    {
        $ticket = $this->abrir();

        $this->actingAs($this->docente());
        Filament::setCurrentPanel('profesores');

        Livewire::test(CourseTickets::class, ['record' => $this->course->getKey()])
            ->callAction(TestAction::make('hilo')->table($ticket), ['mensaje' => 'Probá de nuevo.']);

        $this->assertSame(TicketStatus::Answered, $ticket->fresh()->status);

        // Por contenido y no por «el último»: los dos mensajes caen en el mismo
        // segundo y ordenar por fecha no los distingue
        $this->assertSame(2, $ticket->messages()->count());
        $this->assertTrue($ticket->messages()->where('body', 'Probá de nuevo.')->exists());
    }

    public function test_un_docente_ajeno_no_llega_a_esa_solapa(): void
    {
        $this->actingAs(Teacher::factory()->create()->user)
            ->get("/profesores/courses/{$this->course->getKey()}/consultas")
            ->assertNotFound();
    }
}
