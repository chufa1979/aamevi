<?php

namespace Tests\Feature\Classroom;

use Tests\TestCase;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\CourseClass;
use App\Models\ClassContent;
use App\Models\TaskSubmission;
use App\Enums\SubmissionStatus;
use App\Models\CourseEnrollment;
use App\Services\ProgressService;
use Illuminate\Http\UploadedFile;
use App\Services\SubmissionService;
use App\Exceptions\SubmissionException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Entrega y corrección de trabajos prácticos.
 *
 * Es lo único del ciclo que no se puede automatizar: alguien tiene que leer lo
 * que el alumno subió. Las reglas que protege este archivo son tres — una
 * entrega por tarea salvo desaprobación, la fecha de entrega, y que la nota no
 * se vea hasta que el docente la publique.
 */
class TaskSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private SubmissionService $entregas;

    private ProgressService $progreso;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->entregas = app(SubmissionService::class);
        $this->progreso = app(ProgressService::class);
    }

    private function tarea(?CourseClass $class = null): ClassContent
    {
        return ClassContent::factory()
            ->task()
            ->for($class ?? CourseClass::factory(), 'class')
            ->create(['title' => 'Trabajo práctico 1']);
    }

    private function archivo(): UploadedFile
    {
        return UploadedFile::fake()->create('mi-trabajo.pdf', 120, 'application/pdf');
    }

    // ── Entregar ────────────────────────────────────────────────────────────

    public function test_entregar_guarda_el_archivo_y_la_fila(): void
    {
        $student = Student::factory()->create();
        $tarea = $this->tarea();

        $entrega = $this->entregas->submit($student, $tarea, $this->archivo());

        Storage::disk('public')->assertExists($entrega->file_path);

        $this->assertSame(SubmissionStatus::Pending, $entrega->status);
        $this->assertSame(1, $entrega->attempt_number);
        // El nombre original se conserva aparte: el del disco es un hash
        $this->assertSame('mi-trabajo.pdf', $entrega->file_name);
        $this->assertNull($entrega->grade);
    }

    public function test_no_se_puede_entregar_dos_veces(): void
    {
        $student = Student::factory()->create();
        $tarea = $this->tarea();

        $this->entregas->submit($student, $tarea, $this->archivo());

        $this->expectException(SubmissionException::class);
        $this->expectExceptionMessage('Ya entregaste esta tarea');

        $this->entregas->submit($student, $tarea, $this->archivo());
    }

    /** Tampoco mientras está sin corregir: la reentrega existe para arreglar un rechazo. */
    public function test_tampoco_mientras_esta_sin_corregir(): void
    {
        $student = Student::factory()->create();
        $tarea = $this->tarea();

        $this->entregas->submit($student, $tarea, $this->archivo());

        $this->assertFalse($this->entregas->canSubmit($student, $tarea));
    }

    public function test_se_puede_volver_a_entregar_si_la_desaprueban(): void
    {
        $student = Student::factory()->create();
        $tarea = $this->tarea();
        $teacher = Teacher::factory()->create();

        $primera = $this->entregas->submit($student, $tarea, $this->archivo());
        $this->entregas->grade($primera, $teacher, 4, false, 'Rehacelo.');

        $this->assertTrue($this->entregas->canSubmit($student, $tarea));

        $segunda = $this->entregas->submit($student, $tarea, $this->archivo());

        $this->assertSame(2, $segunda->attempt_number);

        // La corrección original sigue existiendo: una nota reclamada tiene que
        // poder reconstruirse
        $this->assertDatabaseCount('task_submissions', 2);
        $this->assertSame(SubmissionStatus::Rejected, $primera->fresh()->status);
    }

    public function test_no_se_puede_entregar_despues_del_vencimiento(): void
    {
        $student = Student::factory()->create();
        $tarea = ClassContent::factory()->overdue()->create();

        $this->expectException(SubmissionException::class);
        $this->expectExceptionMessage('venció');

        $this->entregas->submit($student, $tarea, $this->archivo());
    }

    public function test_una_tarea_sin_fecha_se_puede_entregar_siempre(): void
    {
        $tarea = $this->tarea();

        $this->assertNull($tarea->due_date);
        $this->assertFalse($tarea->isPastDue());
        $this->assertTrue($this->entregas->canSubmit(Student::factory()->create(), $tarea));
    }

    public function test_no_se_puede_entregar_a_un_contenido_que_no_es_tarea(): void
    {
        $video = ClassContent::factory()->video()->create();

        $this->expectException(SubmissionException::class);
        $this->expectExceptionMessage('no es una tarea');

        $this->entregas->submit(Student::factory()->create(), $video, $this->archivo());
    }

    // ── Corregir ────────────────────────────────────────────────────────────

    public function test_corregir_registra_nota_docente_y_devolucion(): void
    {
        $entrega = TaskSubmission::factory()->create();
        $teacher = Teacher::factory()->create();

        $corregida = $this->entregas->grade($entrega, $teacher, 8.5, true, 'Buen análisis.');

        $this->assertSame(SubmissionStatus::Approved, $corregida->status);
        $this->assertSame('8.50', $corregida->grade);
        $this->assertSame($teacher->id, $corregida->graded_by);
        $this->assertSame('Buen análisis.', $corregida->feedback);
        $this->assertNotNull($corregida->graded_at);
    }

    public function test_no_se_puede_corregir_dos_veces(): void
    {
        $entrega = TaskSubmission::factory()->approved()->create();

        $this->expectException(SubmissionException::class);
        $this->expectExceptionMessage('ya fue corregida');

        $this->entregas->grade($entrega, Teacher::factory()->create(), 9, true);
    }

    public function test_la_nota_tiene_que_estar_en_la_escala(): void
    {
        $entrega = TaskSubmission::factory()->create();

        $this->expectException(SubmissionException::class);
        $this->expectExceptionMessage('fuera de la escala');

        $this->entregas->grade($entrega, Teacher::factory()->create(), 12, true);
    }

    // ── Publicar ────────────────────────────────────────────────────────────

    /** Lo que separa corregir de publicar: hasta que no se publica, el alumno no la ve. */
    public function test_una_correccion_sin_publicar_es_invisible_para_el_alumno(): void
    {
        $entrega = TaskSubmission::factory()->approved()->create();

        $this->assertTrue($entrega->isGraded());
        $this->assertFalse($entrega->isPublished());
        $this->assertFalse($entrega->isVisibleToStudent());
    }

    public function test_publicar_la_hace_visible(): void
    {
        $entrega = TaskSubmission::factory()->approved()->create();

        $publicada = $this->entregas->publish($entrega);

        $this->assertTrue($publicada->isVisibleToStudent());
        $this->assertNotNull($publicada->published_at);
    }

    public function test_no_se_puede_publicar_algo_sin_corregir(): void
    {
        $entrega = TaskSubmission::factory()->create();

        $this->expectException(SubmissionException::class);
        $this->expectExceptionMessage('todavía no fue corregida');

        $this->entregas->publish($entrega);
    }

    // ── El gateo ────────────────────────────────────────────────────────────

    /** La decisión de diseño: entregar hace falta para completar la clase. */
    public function test_una_clase_con_tarea_sin_entregar_no_se_completa(): void
    {
        $class = CourseClass::factory()->create(['activation_date' => now()->subDay()]);
        $this->tarea($class);
        $student = Student::factory()->create();

        CourseEnrollment::factory()->approved()->create([
            'course_id' => $class->module->course_id,
            'student_id' => $student->id,
        ]);

        $this->assertFalse($this->progreso->complete($student, $class->fresh()));
        $this->assertFalse($this->progreso->hasCompleted($student, $class));
    }

    public function test_entregarla_habilita_completar_la_clase(): void
    {
        $class = CourseClass::factory()->create(['activation_date' => now()->subDay()]);
        $tarea = $this->tarea($class);
        $student = Student::factory()->create();

        $this->entregas->submit($student, $tarea, $this->archivo());

        $this->assertTrue($this->progreso->complete($student, $class->fresh()));
    }

    /** No hace falta que se la aprueben: eso la dejaría esperando a otra persona. */
    public function test_alcanza_con_entregar_aunque_este_sin_corregir(): void
    {
        $class = CourseClass::factory()->create(['activation_date' => now()->subDay()]);
        $tarea = $this->tarea($class);
        $student = Student::factory()->create();

        $entrega = $this->entregas->submit($student, $tarea, $this->archivo());

        $this->assertSame(SubmissionStatus::Pending, $entrega->status);
        $this->assertTrue($this->progreso->complete($student, $class->fresh()));
    }

    public function test_el_motivo_dice_que_tarea_falta(): void
    {
        $class = CourseClass::factory()->create(['activation_date' => now()->subDay()]);
        $this->tarea($class);
        $student = Student::factory()->create();

        $this->assertSame(
            'Te falta entregar «Trabajo práctico 1».',
            $this->progreso->completionBlocker($student, $class->fresh()),
        );
    }

    public function test_una_clase_sin_tareas_no_se_traba(): void
    {
        $class = CourseClass::factory()->create(['activation_date' => now()->subDay()]);
        $student = Student::factory()->create();

        $this->assertNull($this->progreso->completionBlocker($student, $class));
        $this->assertTrue($this->progreso->complete($student, $class));
    }
}
