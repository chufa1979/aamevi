<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use Livewire\Livewire;
use App\Models\Student;
use App\Models\Teacher;
use App\Enums\EnrollmentStatus;
use App\Models\CourseEnrollment;
use App\Exceptions\EnrollmentException;
use Illuminate\Database\QueryException;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\RelationManagers\EnrollmentsRelationManager;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_una_inscripcion_nace_pendiente(): void
    {
        $this->assertSame(EnrollmentStatus::Pending, CourseEnrollment::factory()->create()->status);
    }

    public function test_aprobar_registra_quien_y_cuando(): void
    {
        $enrollment = CourseEnrollment::factory()->create();
        $teacher = Teacher::factory()->create();

        $enrollment->approve($teacher);

        $this->assertSame(EnrollmentStatus::Approved, $enrollment->status);
        $this->assertSame($teacher->id, $enrollment->approved_by);
        $this->assertNotNull($enrollment->approval_date);
    }

    public function test_rechazar_deja_la_inscripcion_rechazada(): void
    {
        $enrollment = CourseEnrollment::factory()->create();

        $enrollment->reject(Teacher::factory()->create());

        $this->assertSame(EnrollmentStatus::Rejected, $enrollment->status);
    }

    public function test_no_se_puede_aprobar_una_inscripcion_ya_rechazada(): void
    {
        $enrollment = CourseEnrollment::factory()->rejected()->create();

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('Rechazada');

        $enrollment->approve();
    }

    public function test_no_se_puede_finalizar_una_inscripcion_que_no_esta_en_curso(): void
    {
        $enrollment = CourseEnrollment::factory()->approved()->create();

        $this->expectException(EnrollmentException::class);

        $enrollment->complete();
    }

    public function test_el_recorrido_completo_de_estados(): void
    {
        $enrollment = CourseEnrollment::factory()->create();

        $enrollment->approve();
        $enrollment->activate();
        $enrollment->complete();

        $this->assertSame(EnrollmentStatus::Completed, $enrollment->status);
    }

    public function test_no_se_puede_aprobar_si_el_curso_esta_lleno(): void
    {
        $course = Course::factory()->create(['max_students' => 2]);

        CourseEnrollment::factory()->count(2)->approved()->create(['course_id' => $course->id]);

        $enrollment = CourseEnrollment::factory()->create(['course_id' => $course->id]);

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('cupo de 2');

        $enrollment->approve();
    }

    /** Las rechazadas y las pendientes no deben ocupar lugar. */
    public function test_las_inscripciones_sin_resolver_no_ocupan_cupo(): void
    {
        $course = Course::factory()->create(['max_students' => 1]);

        CourseEnrollment::factory()->rejected()->create(['course_id' => $course->id]);
        CourseEnrollment::factory()->create(['course_id' => $course->id]);

        $this->assertSame(0, $course->occupiedSeats());
        $this->assertFalse($course->isFull());
    }

    public function test_un_alumno_no_puede_inscribirse_dos_veces_al_mismo_curso(): void
    {
        $course = Course::factory()->create();
        $student = Student::factory()->create();

        CourseEnrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);

        $this->expectException(QueryException::class);

        CourseEnrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);
    }

    public function test_el_panel_aprueba_desde_la_tabla(): void
    {
        $course = Course::factory()->create();
        $enrollment = CourseEnrollment::factory()->create(['course_id' => $course->id]);

        Livewire::test(EnrollmentsRelationManager::class, [
            'ownerRecord' => $course,
            'pageClass' => EditCourse::class,
        ])
            ->callAction(TestAction::make('approve')->table($enrollment))
            ->assertHasNoActionErrors();

        $this->assertSame(EnrollmentStatus::Approved, $enrollment->refresh()->status);
    }

    public function test_el_panel_avisa_en_lugar_de_romper_cuando_el_curso_esta_lleno(): void
    {
        $course = Course::factory()->create(['max_students' => 1]);
        CourseEnrollment::factory()->approved()->create(['course_id' => $course->id]);
        $enrollment = CourseEnrollment::factory()->create(['course_id' => $course->id]);

        Livewire::test(EnrollmentsRelationManager::class, [
            'ownerRecord' => $course,
            'pageClass' => EditCourse::class,
        ])
            ->callAction(TestAction::make('approve')->table($enrollment));

        // Sigue pendiente: la acción avisa y no cambia el estado
        $this->assertSame(EnrollmentStatus::Pending, $enrollment->refresh()->status);
    }

    public function test_borrar_el_docente_que_aprobo_no_borra_la_inscripcion(): void
    {
        $teacher = Teacher::factory()->create();
        $enrollment = CourseEnrollment::factory()->create();
        $enrollment->approve($teacher);

        $teacher->user->delete();

        $enrollment->refresh();

        $this->assertNotNull($enrollment->id);
        $this->assertNull($enrollment->approved_by);
        $this->assertSame(EnrollmentStatus::Approved, $enrollment->status);
    }
}
