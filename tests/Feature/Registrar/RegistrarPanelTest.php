<?php

namespace Tests\Feature\Registrar;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use Livewire\Livewire;
use App\Models\Student;
use App\Models\Teacher;
use App\Enums\EnrollmentStatus;
use App\Models\CourseEnrollment;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\Students\Pages\EditStudent;
use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Filament\Resources\EnrollmentRequests\Pages\ManageEnrollmentRequests;

/**
 * El panel del perfil administrativo.
 *
 * Mismo criterio que TeacherPanelTest: no hace falta probar que las pantallas
 * existen —son las mismas que /admin—, sino que el recorte aguante: qué ve y
 * qué no ve este perfil, y qué le queda vedado aunque escriba la URL a mano.
 */
class RegistrarPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_invitado_no_entra(): void
    {
        $this->get('/administracion')->assertRedirect('/login');
    }

    public function test_un_alumno_no_entra(): void
    {
        $this->actingAs(User::factory()->student()->create())
            ->get('/administracion')
            ->assertForbidden();
    }

    public function test_un_docente_no_entra(): void
    {
        $this->actingAs(Teacher::factory()->create()->user)
            ->get('/administracion')
            ->assertForbidden();
    }

    /** El panel de administración es un superconjunto: no hace falta la otra puerta. */
    public function test_un_administrador_no_entra_al_panel_administrativo(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/administracion')
            ->assertForbidden();
    }

    public function test_un_administrativo_desactivado_no_entra(): void
    {
        $registrar = User::factory()->registrar()->inactive()->create();

        $this->actingAs($registrar)->get('/administracion')->assertForbidden();
    }

    /** Sin tablero: entra directo a las solicitudes, que es con lo que trabaja. */
    public function test_entra_y_cae_en_las_solicitudes(): void
    {
        $this->actingAs(User::factory()->registrar()->create())
            ->get('/administracion')
            ->assertRedirect('/administracion/solicitudes');
    }

    public function test_el_panel_no_expone_su_propio_login(): void
    {
        $this->get('/administracion/login')->assertNotFound();
    }

    /** Las cuentas de docente o administrador no están a su alcance. */
    public function test_el_panel_no_tiene_pantalla_de_usuarios(): void
    {
        $this->actingAs(User::factory()->registrar()->create())
            ->get('/administracion/users')
            ->assertNotFound();
    }

    public function test_ve_solicitudes_de_todos_los_cursos(): void
    {
        $uno = CourseEnrollment::factory()->create(['course_id' => Course::factory()->create(['title' => 'Nutrición'])]);
        $otro = CourseEnrollment::factory()->create(['course_id' => Course::factory()->create(['title' => 'Sueño'])]);

        $this->actingAs(User::factory()->registrar()->create())
            ->get('/administracion/solicitudes')
            ->assertSuccessful()
            ->assertSee('Nutrición')
            ->assertSee('Sueño');
    }

    public function test_puede_aprobar_una_solicitud(): void
    {
        $enrollment = CourseEnrollment::factory()->create();

        Livewire::actingAs(User::factory()->registrar()->create())
            ->test(ManageEnrollmentRequests::class)
            ->callAction(TestAction::make('approve')->table($enrollment))
            ->assertHasNoActionErrors();

        $this->assertSame(EnrollmentStatus::Approved, $enrollment->refresh()->status);
    }

    public function test_puede_rechazar_una_solicitud(): void
    {
        $enrollment = CourseEnrollment::factory()->create();

        Livewire::actingAs(User::factory()->registrar()->create())
            ->test(ManageEnrollmentRequests::class)
            ->callAction(TestAction::make('reject')->table($enrollment))
            ->assertHasNoActionErrors();

        $this->assertSame(EnrollmentStatus::Rejected, $enrollment->refresh()->status);
    }

    public function test_puede_inscribir_un_alumno_directo(): void
    {
        $course = Course::factory()->create();
        $student = Student::factory()->create();

        Livewire::actingAs(User::factory()->registrar()->create())
            ->test(ManageEnrollmentRequests::class)
            ->callAction(TestAction::make('create')->table(), data: [
                'course_id' => $course->getKey(),
                'student_id' => $student->getKey(),
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $course->getKey(),
            'student_id' => $student->getKey(),
            'status' => EnrollmentStatus::Approved->value,
        ]);
    }

    public function test_puede_dar_de_alta_un_alumno(): void
    {
        Livewire::actingAs(User::factory()->registrar()->create())
            ->test(CreateStudent::class)
            ->fillForm([
                'first_name' => 'Rocío',
                'last_name' => 'Aguirre',
                'email' => 'rocio@aamevi.ar',
                'password' => 'password',
                'is_active' => true,
                'student' => ['dni' => '38111222'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'rocio@aamevi.ar')->firstOrFail();

        $this->assertTrue($user->isStudent());
        $this->assertSame('38111222', $user->student->dni);
    }

    /** Borrar una cuenta sigue siendo del administrador, aunque sea de alumno. */
    public function test_no_puede_eliminar_un_alumno(): void
    {
        $student = Student::factory()->create();

        Livewire::actingAs(User::factory()->registrar()->create())
            ->test(EditStudent::class, ['record' => $student->getKey()])
            ->assertActionHidden('delete');

        $this->assertNotNull($student->fresh());
    }

    /** `StudentResource` sólo lista alumnos: un docente no aparece ahí ni por URL directa. */
    public function test_no_llega_a_un_docente_por_la_ficha_de_alumno(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs(User::factory()->registrar()->create())
            ->get("/administracion/alumnos/{$teacher->getKey()}/edit")
            ->assertNotFound();
    }
}
