<?php

namespace Tests\Feature\Classroom;

use Tests\TestCase;
use App\Models\Course;
use App\Models\Student;
use App\Enums\EnrollmentStatus;
use App\Models\CourseEnrollment;
use App\Services\EnrollmentService;
use App\Exceptions\EnrollmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Pedir la inscripción es lo único del ciclo que hace el alumno; el resto
 * —aprobar, rechazar, activar— lo resuelve `CourseEnrollment`.
 */
class EnrollmentRequestTest extends TestCase
{
    use RefreshDatabase;

    private EnrollmentService $inscripciones;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inscripciones = app(EnrollmentService::class);
    }

    public function test_la_solicitud_nace_pendiente(): void
    {
        $enrollment = $this->inscripciones->request(Student::factory()->create(), Course::factory()->create());

        $this->assertSame(EnrollmentStatus::Pending, $enrollment->status);
        $this->assertTrue($enrollment->isPending(), 'El estado no llegó al modelo recién creado.');
    }

    public function test_no_se_puede_pedir_dos_veces_el_mismo_curso(): void
    {
        $student = Student::factory()->create();
        $course = Course::factory()->create();

        $this->inscripciones->request($student, $course);

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('Ya tenés una solicitud');

        $this->inscripciones->request($student, $course);
    }

    /** Ni siquiera después de que se la rechacen: insistir sin que nada cambie no sirve. */
    public function test_tampoco_despues_de_un_rechazo(): void
    {
        $student = Student::factory()->create();
        $course = Course::factory()->create();

        $this->inscripciones->request($student, $course)->reject();

        $this->expectException(EnrollmentException::class);

        $this->inscripciones->request($student, $course);
    }

    public function test_no_se_puede_pedir_un_curso_lleno(): void
    {
        $course = Course::factory()->create(['max_students' => 1]);
        CourseEnrollment::factory()->approved()->create(['course_id' => $course->id]);

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('cupo de 1');

        $this->inscripciones->request(Student::factory()->create(), $course);
    }

    public function test_no_se_puede_pedir_un_curso_inactivo(): void
    {
        $course = Course::factory()->create(['is_active' => false]);

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('no está abierto');

        $this->inscripciones->request(Student::factory()->create(), $course);
    }

    // ── El catálogo ─────────────────────────────────────────────────────────

    public function test_el_catalogo_ofrece_los_cursos_activos_con_lugar(): void
    {
        $student = Student::factory()->create();
        $abierto = Course::factory()->create(['max_students' => 10]);

        $this->assertTrue(Course::availableFor($student)->get()->contains($abierto));
    }

    public function test_el_catalogo_no_ofrece_cursos_inactivos(): void
    {
        $student = Student::factory()->create();
        $cerrado = Course::factory()->create(['is_active' => false]);

        $this->assertFalse(Course::availableFor($student)->get()->contains($cerrado));
    }

    public function test_el_catalogo_no_ofrece_cursos_llenos(): void
    {
        $student = Student::factory()->create();
        $lleno = Course::factory()->create(['max_students' => 2]);

        CourseEnrollment::factory()->count(2)->approved()->create(['course_id' => $lleno->id]);

        $this->assertFalse(Course::availableFor($student)->get()->contains($lleno));
    }

    /** Las pendientes y rechazadas no ocupan cupo, así que el curso sigue ofreciéndose a los demás. */
    public function test_las_solicitudes_sin_resolver_no_llenan_el_curso(): void
    {
        $otro = Student::factory()->create();
        $course = Course::factory()->create(['max_students' => 1]);

        CourseEnrollment::factory()->create(['course_id' => $course->id]);
        CourseEnrollment::factory()->rejected()->create(['course_id' => $course->id]);

        $this->assertTrue(Course::availableFor($otro)->get()->contains($course));
    }

    public function test_el_catalogo_no_ofrece_un_curso_que_el_alumno_ya_pidio(): void
    {
        $student = Student::factory()->create();
        $course = Course::factory()->create();

        $this->inscripciones->request($student, $course);

        $this->assertFalse(Course::availableFor($student)->get()->contains($course));
    }
}
