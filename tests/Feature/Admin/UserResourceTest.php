<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use Livewire\Livewire;
use App\Enums\UserRole;
use App\Models\Teacher;
use Illuminate\Support\Facades\Hash;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\CreateUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_crear_un_profesor_tambien_crea_su_ficha(): void
    {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'first_name' => 'Marta',
                'last_name' => 'Gómez',
                'email' => 'marta@aamevi.ar',
                'role' => UserRole::Teacher->value,
                'password' => 'password',
                'is_active' => true,
                'teacher' => [
                    'specialization' => 'Nutrición',
                    'bio' => 'Docente de prueba.',
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'marta@aamevi.ar')->firstOrFail();

        $this->assertTrue($user->isTeacher());
        $this->assertNotNull($user->teacher, 'El profesor quedó sin ficha en `teachers`.');
        $this->assertSame('Nutrición', $user->teacher->specialization);
        $this->assertSame($user->id, $user->teacher->id);
    }

    public function test_crear_un_alumno_tambien_crea_su_ficha(): void
    {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'first_name' => 'Carolina',
                'last_name' => 'Ruiz',
                'email' => 'caro@aamevi.ar',
                'role' => UserRole::Student->value,
                'password' => 'password',
                'is_active' => true,
                'student' => [
                    'dni' => '31222333',
                    'delegation' => 'Buenos Aires',
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'caro@aamevi.ar')->firstOrFail();

        $this->assertNotNull($user->student, 'El alumno quedó sin ficha en `students`.');
        $this->assertSame('31222333', $user->student->dni);
        $this->assertSame($user->id, $user->student->id);
    }

    public function test_un_administrador_no_genera_fichas(): void
    {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'first_name' => 'Admin',
                'last_name' => 'Nuevo',
                'email' => 'admin2@aamevi.ar',
                'role' => UserRole::Admin->value,
                'password' => 'password',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'admin2@aamevi.ar')->firstOrFail();

        $this->assertNull($user->student);
        $this->assertNull($user->teacher);
    }

    public function test_editar_sin_tocar_la_contrasena_la_conserva(): void
    {
        $teacher = Teacher::factory()->create();
        $user = $teacher->user;
        $hashOriginal = $user->password;

        Livewire::test(EditUser::class, ['record' => $user->getKey()])
            ->fillForm(['first_name' => 'Renombrado'])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertSame('Renombrado', $user->first_name);
        $this->assertSame($hashOriginal, $user->password);
    }

    public function test_editar_cambiando_la_contrasena_la_actualiza(): void
    {
        $user = User::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->getKey()])
            ->fillForm(['password' => 'nuevaClave123'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('nuevaClave123', $user->refresh()->password));
    }
}
