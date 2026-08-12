<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_clave_primaria_es_un_uuid(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(Str::isUuid($user->id));
    }

    public function test_la_contrasena_se_guarda_hasheada(): void
    {
        $user = User::factory()->create(['password' => 'secreto123']);

        $this->assertNotSame('secreto123', $user->password);
        $this->assertTrue(Hash::check('secreto123', $user->password));
    }

    public function test_el_rol_se_castea_al_enum(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertSame(UserRole::Admin, $user->fresh()->role);
        $this->assertSame('Administrador', $user->role->label());
        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isStudent());
    }

    public function test_full_name_concatena_nombre_y_apellido(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
        ]);

        $this->assertSame('Ana Pérez', $user->full_name);
    }

    public function test_la_ficha_de_alumno_comparte_la_clave_del_usuario(): void
    {
        $student = Student::factory()->create();

        $this->assertSame($student->id, $student->user->id);
        $this->assertTrue($student->user->isStudent());
        $this->assertSame($student->id, $student->user->student->id);
    }

    public function test_la_ficha_de_profesor_comparte_la_clave_del_usuario(): void
    {
        $teacher = Teacher::factory()->create();

        $this->assertSame($teacher->id, $teacher->user->id);
        $this->assertTrue($teacher->user->isTeacher());
        $this->assertSame($teacher->id, $teacher->user->teacher->id);
    }

    public function test_borrar_el_usuario_arrastra_su_ficha(): void
    {
        $student = Student::factory()->create();
        $id = $student->id;

        $student->user->delete();

        $this->assertDatabaseMissing('students', ['id' => $id]);
        $this->assertDatabaseMissing('users', ['id' => $id]);
    }

    public function test_el_email_es_unico(): void
    {
        User::factory()->create(['email' => 'repetido@aamevi.ar']);

        $this->expectException(QueryException::class);

        User::factory()->create(['email' => 'repetido@aamevi.ar']);
    }

    public function test_una_cuenta_de_google_no_necesita_contrasena(): void
    {
        $user = User::factory()->fromGoogle()->create();

        $this->assertNull($user->password);
        $this->assertSame('google', $user->oauth_provider);
    }

    public function test_el_usuario_requiere_verificar_su_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->assertFalse($user->hasVerifiedEmail());

        $user->markEmailAsVerified();

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_el_seeder_crea_un_usuario_por_rol(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', ['email' => 'admin@aamevi.ar', 'role' => 'admin']);
        $this->assertDatabaseHas('users', ['email' => 'profesor@aamevi.ar', 'role' => 'teacher']);
        $this->assertDatabaseHas('users', ['email' => 'alumno@aamevi.ar', 'role' => 'student']);
        $this->assertDatabaseCount('teachers', 1);
        $this->assertDatabaseCount('students', 1);

        // Idempotente: correrlo dos veces no duplica ni falla
        $this->seed();

        $this->assertDatabaseCount('users', 3);
    }
}
