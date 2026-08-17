<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use RuntimeException;
use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use App\Enums\EmailType;
use App\Enums\EmailStatus;
use App\Models\CourseClass;
use App\Models\QueuedEmail;
use App\Models\ClassContent;
use App\Models\CourseModule;
use App\Models\CourseEnrollment;
use App\Services\ProgressService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use App\Services\SubmissionService;
use App\Services\CertificateService;
use Illuminate\Support\Facades\Mail;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mailer\SentMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Los avisos por email.
 *
 * Nada se manda en el momento: todo pasa por `email_queue` y sale cuando el
 * worker la vacía. Lo que se prueba acá es que cada hecho encole lo suyo, que la
 * nota no se anuncie antes de publicarla, y que un envío fallido reintente en
 * lugar de perderse.
 */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $avisos;

    private SubmissionService $entregas;

    private ProgressService $progreso;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->avisos = app(NotificationService::class);
        $this->entregas = app(SubmissionService::class);
        $this->progreso = app(ProgressService::class);
    }

    /**
     * Los correos que realmente salieron.
     *
     * `phpunit.xml` usa el mailer `array`, así que en vez de `Mail::fake()` se
     * mira el transporte: `assertSentCount` cuenta Mailables, y acá se manda
     * HTML ya renderizado, que no lo es. Esto además comprueba lo que importa
     * —a quién fue y con qué asunto—, no sólo que se haya llamado al mailer.
     *
     * @return Collection<int, SentMessage>
     */
    private function salieron()
    {
        return app('mailer')->getSymfonyTransport()->messages();
    }

    /** @return array{0: Course, 1: Student, 2: CourseClass} */
    private function cursoConAlumno(int $clases = 1): array
    {
        $course = Course::factory()->create(['title' => 'Medicina del estilo de vida']);
        $module = CourseModule::factory()->for($course)->create(['order_number' => 1]);

        $primera = null;

        for ($i = 1; $i <= $clases; $i++) {
            $class = CourseClass::factory()->for($module, 'module')->create([
                'order_number' => $i,
                'activation_date' => now()->subDays(10),
            ]);

            $primera ??= $class;
        }

        $student = Student::factory()->create();

        CourseEnrollment::factory()->approved()->create([
            'course_id' => $course->getKey(),
            'student_id' => $student->getKey(),
        ]);

        return [$course->fresh(), $student, $primera];
    }

    // ── Qué encola cada hecho ───────────────────────────────────────────────

    public function test_aprobar_una_inscripcion_encola_el_aviso(): void
    {
        $course = Course::factory()->create(['title' => 'Nutrición aplicada']);
        $student = Student::factory()->create();

        $enrollment = CourseEnrollment::factory()->create([
            'course_id' => $course->getKey(),
            'student_id' => $student->getKey(),
        ]);

        $enrollment->approve(Teacher::factory()->create());

        $aviso = QueuedEmail::where('recipient_id', $student->getKey())->firstOrFail();

        $this->assertSame(EmailType::EnrollmentApproved, $aviso->email_type);
        $this->assertStringContainsString('Nutrición aplicada', $aviso->subject);
        $this->assertStringContainsString($student->user->first_name, $aviso->body);
    }

    /** Rechazar no avisa: la mala noticia por correo automático es peor que un llamado. */
    public function test_rechazar_no_encola_nada(): void
    {
        $enrollment = CourseEnrollment::factory()->create();

        $enrollment->reject(Teacher::factory()->create());

        $this->assertSame(0, QueuedEmail::count());
    }

    public function test_publicar_una_correccion_encola_el_aviso_con_la_nota(): void
    {
        [$course, $student, $class] = $this->cursoConAlumno();

        $tarea = ClassContent::factory()->task()->for($class, 'class')->create(['title' => 'Trabajo práctico 1']);
        $entrega = $this->entregas->submit($student, $tarea, UploadedFile::fake()->create('tp.pdf', 20, 'application/pdf'));
        $entrega = $this->entregas->grade($entrega, Teacher::factory()->create(), 8, true, 'Buen trabajo');

        // Corregida y sin publicar todavía no hay nada que contar
        $this->assertSame(0, QueuedEmail::where('email_type', EmailType::TaskGraded)->count());

        $this->entregas->publish($entrega);

        $aviso = QueuedEmail::where('email_type', EmailType::TaskGraded)->firstOrFail();

        $this->assertStringContainsString('Trabajo práctico 1', $aviso->subject);
        $this->assertStringContainsString('Buen trabajo', $aviso->body);
        $this->assertStringContainsString('8', $aviso->body);
    }

    public function test_emitir_un_certificado_encola_el_aviso_con_el_numero(): void
    {
        [$course, $student, $class] = $this->cursoConAlumno();

        $this->progreso->complete($student, $class);

        $certificado = app(CertificateService::class)->of($student, $course);

        $aviso = QueuedEmail::where('email_type', EmailType::Certificate)->firstOrFail();

        $this->assertNotNull($certificado);
        $this->assertStringContainsString($certificado->certificate_number, $aviso->body);
    }

    // ── Recordatorios ───────────────────────────────────────────────────────

    public function test_el_recordatorio_sale_para_las_clases_en_vivo_de_manana(): void
    {
        [$course, $student] = $this->cursoConAlumno();

        $module = $course->modules()->first();

        CourseClass::factory()->for($module, 'module')->create([
            'order_number' => 9,
            'title' => 'Encuentro sincrónico',
            'is_live_session' => true,
            'activation_date' => now()->addHours(20),
        ]);

        $this->artisan('emails:recordatorios')->assertSuccessful();

        $aviso = QueuedEmail::where('email_type', EmailType::ClassReminder)->firstOrFail();

        $this->assertSame($student->getKey(), $aviso->recipient_id);
        $this->assertStringContainsString('Encuentro sincrónico', $aviso->subject);
    }

    /** Una clase grabada no tiene hora de encuentro: avisar de cada una sería spam. */
    public function test_no_hay_recordatorio_de_una_clase_que_no_es_en_vivo(): void
    {
        [$course] = $this->cursoConAlumno();

        CourseClass::factory()->for($course->modules()->first(), 'module')->create([
            'order_number' => 9,
            'is_live_session' => false,
            'activation_date' => now()->addHours(20),
        ]);

        $this->artisan('emails:recordatorios')->assertSuccessful();

        $this->assertSame(0, QueuedEmail::where('email_type', EmailType::ClassReminder)->count());
    }

    public function test_correr_el_comando_dos_veces_no_duplica_el_recordatorio(): void
    {
        [$course] = $this->cursoConAlumno();

        CourseClass::factory()->for($course->modules()->first(), 'module')->create([
            'order_number' => 9,
            'title' => 'Encuentro sincrónico',
            'is_live_session' => true,
            'activation_date' => now()->addHours(20),
        ]);

        $this->artisan('emails:recordatorios');
        $this->artisan('emails:recordatorios');

        $this->assertSame(1, QueuedEmail::where('email_type', EmailType::ClassReminder)->count());
    }

    // ── El worker ───────────────────────────────────────────────────────────

    public function test_el_worker_manda_los_que_ya_vencieron(): void
    {
        QueuedEmail::factory()->count(2)->create();
        $futuro = QueuedEmail::factory()->scheduled()->create();

        $this->artisan('emails:enviar')->assertSuccessful();

        $this->assertSame(2, QueuedEmail::where('status', EmailStatus::Sent)->count());
        $this->assertTrue($futuro->fresh()->isPending());
        $this->assertCount(2, $this->salieron());
    }

    public function test_el_correo_sale_al_destinatario_con_su_asunto_y_su_cuerpo(): void
    {
        $aviso = QueuedEmail::factory()->create([
            'subject' => 'Ya podés empezar Nutrición',
            'body' => '<p>Hola Camila.</p>',
        ]);

        $this->artisan('emails:enviar')->assertSuccessful();

        $mensaje = $this->salieron()->first()->getOriginalMessage();

        $this->assertSame('Ya podés empezar Nutrición', $mensaje->getSubject());
        $this->assertSame($aviso->recipient->email, $mensaje->getTo()[0]->getAddress());
        $this->assertStringContainsString('Hola Camila.', $mensaje->getHtmlBody());
    }

    public function test_lo_ya_enviado_no_se_manda_de_nuevo(): void
    {
        QueuedEmail::factory()->sent()->create();

        $this->artisan('emails:enviar')->assertSuccessful();

        $this->assertCount(0, $this->salieron());
    }

    public function test_el_limite_corta_la_tanda(): void
    {
        QueuedEmail::factory()->count(5)->create();

        $this->artisan('emails:enviar', ['--limite' => 2])->assertSuccessful();

        $this->assertSame(2, QueuedEmail::where('status', EmailStatus::Sent)->count());
    }

    /** Borrar la cuenta se lleva sus avisos: no queda un correo sin dueño esperando salir. */
    public function test_borrar_al_destinatario_borra_sus_avisos(): void
    {
        $aviso = QueuedEmail::factory()->create();

        User::whereKey($aviso->recipient_id)->delete();

        $this->assertSame(0, QueuedEmail::count());
    }

    public function test_un_envio_fallido_reintenta_antes_de_rendirse(): void
    {
        $aviso = QueuedEmail::factory()->create();

        // Se simula la caída del proveedor: lo que importa no es qué falló sino
        // que el aviso no se pierda por eso
        Mail::shouldReceive('html')->andThrow(new RuntimeException('SMTP caído'));

        for ($i = 1; $i < NotificationService::MAX_INTENTOS; $i++) {
            $this->avisos->send($aviso);

            $aviso = $aviso->fresh();

            $this->assertTrue($aviso->isPending(), "Se rindió en el intento {$i}.");
            $this->assertSame($i, $aviso->retry_count);
            $this->assertStringContainsString('SMTP caído', $aviso->last_error);
        }

        $this->avisos->send($aviso);

        $this->assertSame(EmailStatus::Failed, $aviso->fresh()->status);
    }

    public function test_el_reintento_manual_lo_devuelve_a_la_cola(): void
    {
        $aviso = QueuedEmail::factory()->failed()->create();

        $vuelto = $this->avisos->retry($aviso);

        $this->assertTrue($vuelto->isPending());
        $this->assertSame(0, $vuelto->retry_count);
        $this->assertNull($vuelto->last_error);
    }

    // ── El panel ────────────────────────────────────────────────────────────

    public function test_el_administrador_ve_la_cola(): void
    {
        QueuedEmail::factory()->create(['subject' => 'Ya podés empezar Nutrición']);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/avisos')
            ->assertSuccessful()
            ->assertSee('Ya podés empezar Nutrición');
    }

    public function test_el_panel_de_profesores_no_tiene_la_cola(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user)->get('/profesores/avisos')->assertNotFound();
    }
}
