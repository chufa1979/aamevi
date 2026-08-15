<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use Livewire\Livewire;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\CourseEnrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\Students\StudentResource;
use App\Filament\Resources\Students\Pages\ListStudents;
use App\Filament\Resources\Students\Pages\CreateStudent;

class StudentResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_el_listado_abre(): void
    {
        $this->get(StudentResource::getUrl('index'))->assertSuccessful();
    }

    /** La pantalla es de alumnos: un profesor o un administrador no tienen nada que hacer ahí. */
    public function test_el_listado_solo_muestra_alumnos(): void
    {
        $alumno = Student::factory()->create()->user;
        $profesor = User::factory()->create(['role' => UserRole::Teacher]);

        Livewire::test(ListStudents::class)
            ->assertCanSeeTableRecords([$alumno])
            ->assertCanNotSeeTableRecords([$profesor]);
    }

    /** Un alumno sin usuario no podría entrar: el alta tiene que crear los dos. */
    public function test_el_alta_crea_la_cuenta_y_la_ficha(): void
    {
        Livewire::test(CreateStudent::class)
            ->fillForm([
                'first_name' => 'Lucía',
                'last_name' => 'Fernández',
                'email' => 'lucia@aamevi.ar',
                'password' => 'password',
                'is_active' => true,
                'student' => [
                    'dni' => '35123456',
                    'delegation' => 'Buenos Aires',
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'lucia@aamevi.ar')->firstOrFail();

        $this->assertSame(UserRole::Student, $user->role, 'El rol se fija solo, sin selector.');
        $this->assertNotNull($user->student);
        $this->assertSame('35123456', $user->student->dni);
    }

    public function test_la_columna_de_cursos_cuenta_las_inscripciones(): void
    {
        $student = Student::factory()->create();
        CourseEnrollment::factory()->count(2)->create(['student_id' => $student->id]);

        $fila = User::query()->withCount('enrollments')->find($student->id);

        $this->assertSame(2, $fila->enrollments_count);
    }

    public function test_se_pueden_filtrar_los_alumnos_sin_curso(): void
    {
        $conCurso = Student::factory()->create();
        CourseEnrollment::factory()->create(['student_id' => $conCurso->id]);

        $sinCurso = Student::factory()->create();

        Livewire::test(ListStudents::class)
            ->filterTable('sin_curso', true)
            ->assertCanSeeTableRecords([$sinCurso->user])
            ->assertCanNotSeeTableRecords([$conCurso->user]);
    }
}
