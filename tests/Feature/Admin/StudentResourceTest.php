<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use Livewire\Livewire;
use App\Enums\UserRole;
use App\Models\Student;
use App\Enums\EmailType;
use App\Models\QueuedEmail;
use App\Models\CourseEnrollment;
use Filament\Actions\Testing\TestAction;
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
                    'city' => 'Buenos Aires',
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

    /**
     * El número solo no dice a qué curso: el tooltip lista los títulos.
     *
     * El texto va dentro de un atributo `x-tooltip` codificado como JSON
     * (`Js::from()`), que escapa los caracteres no ASCII —por eso el título de
     * prueba no lleva tildes: de lo contrario habría que buscar el `í`
     * en vez del texto legible.
     */
    public function test_la_columna_de_cursos_muestra_los_titulos_en_el_tooltip(): void
    {
        $student = Student::factory()->create();
        CourseEnrollment::factory()->create([
            'student_id' => $student->id,
            'course_id' => Course::factory()->create(['title' => 'Nutricion aplicada']),
        ]);

        Livewire::test(ListStudents::class)->assertSee('Nutricion aplicada', escape: false);
    }

    public function test_se_puede_agregar_una_anotacion_a_la_bitacora_del_alumno(): void
    {
        $student = Student::factory()->create();

        Livewire::test(ListStudents::class)->callAction(
            TestAction::make('bitacora')->table($student->user),
            ['nota' => 'Pago confirmado por Mercado Pago, alias mp.alumno'],
        );

        $this->assertDatabaseHas('student_notes', [
            'student_id' => $student->id,
            'body' => 'Pago confirmado por Mercado Pago, alias mp.alumno',
        ]);
    }

    /** La bitácora es interna: agregar una anotación no encola ningún email. */
    public function test_la_bitacora_del_alumno_no_manda_ningun_correo(): void
    {
        $student = Student::factory()->create();

        Livewire::test(ListStudents::class)->callAction(
            TestAction::make('bitacora')->table($student->user),
            ['nota' => 'No pagó todavía.'],
        );

        $this->assertSame(0, QueuedEmail::count());
    }

    public function test_la_bitacora_del_alumno_muestra_lo_ya_anotado(): void
    {
        $student = Student::factory()->create();
        $student->user->notes()->create(['body' => 'NO PAGÓ', 'created_at' => now()]);

        $modal = Livewire::test(ListStudents::class)
            ->mountAction(TestAction::make('bitacora')->table($student->user));

        $html = (string) $modal->instance()->getMountedAction()->getModalContent()->render();

        $this->assertStringContainsString('NO PAGÓ', $html);
    }

    public function test_se_puede_enviar_un_aviso_administrativo_al_alumno(): void
    {
        $student = Student::factory()->create();

        Livewire::test(ListStudents::class)
            ->callAction(TestAction::make('avisar')->table($student->user), [
                'asunto' => 'Falta un dato en tu inscripción',
                'mensaje' => 'Necesitamos que nos confirmes tu número de socio.',
            ])
            ->assertHasNoActionErrors();

        $aviso = QueuedEmail::where('recipient_id', $student->id)->firstOrFail();

        $this->assertSame(EmailType::AdministrativeNotice, $aviso->email_type);
        $this->assertSame('Falta un dato en tu inscripción', $aviso->subject);
        $this->assertStringContainsString('Necesitamos que nos confirmes tu número de socio.', $aviso->body);
    }
}
