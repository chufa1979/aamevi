<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\Student;
use App\Enums\EmailType;
use App\Models\QueuedEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Alta de cuenta y verificación del correo.
 *
 * Registrarse no da acceso a nada: crea la cuenta y deja al alumno esperando
 * verificar. Lo que se prueba acá es ese corte —que sin verificar el aula esté
 * cerrada— y que nadie pueda elegir su propio rol desde un formulario público.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, string> */
    private function datos(array $cambios = []): array
    {
        return [
            'first_name' => 'Camila',
            'last_name' => 'Ferreyra',
            'email' => 'camila@example.com',
            'password' => 'contraseña-larga-8',
            'password_confirmation' => 'contraseña-larga-8',
            ...$cambios,
        ];
    }

    public function test_el_formulario_abre(): void
    {
        $this->get('/registro')->assertSuccessful()->assertSee('Crear cuenta');
    }

    public function test_crear_la_cuenta_deja_usuario_y_ficha_de_alumno(): void
    {
        $this->post('/registro', $this->datos(['dni' => '30111222']))
            ->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'camila@example.com')->firstOrFail();

        $this->assertSame(UserRole::Student, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull(Student::find($user->getKey()));
        $this->assertSame('30111222', $user->student->dni);
        $this->assertAuthenticatedAs($user);
    }

    /** El DNI es opcional: pedirlo obligatorio traba el alta por un dato que puede esperar. */
    public function test_el_dni_puede_quedar_vacio(): void
    {
        $this->post('/registro', $this->datos())->assertRedirect(route('verification.notice'));

        $this->assertNull(User::where('email', 'camila@example.com')->firstOrFail()->student->dni);
    }

    public function test_el_aviso_de_verificacion_va_a_la_cola(): void
    {
        $this->post('/registro', $this->datos());

        $aviso = QueuedEmail::where('email_type', EmailType::Verification)->firstOrFail();

        $this->assertStringContainsString('/verificar-email/', $aviso->body);
        $this->assertStringContainsString('signature=', $aviso->body);
    }

    /** Un formulario público que pudiera elegir rol sería una puerta abierta. */
    public function test_no_se_puede_pedir_ser_administrador(): void
    {
        $this->post('/registro', $this->datos(['role' => 'admin']));

        $this->assertSame(UserRole::Student, User::where('email', 'camila@example.com')->firstOrFail()->role);
    }

    public function test_el_correo_no_se_puede_repetir(): void
    {
        User::factory()->create(['email' => 'camila@example.com']);

        $this->post('/registro', $this->datos())->assertSessionHasErrors('email');

        $this->assertSame(1, User::where('email', 'camila@example.com')->count());
    }

    public function test_las_contrasenas_tienen_que_coincidir(): void
    {
        $this->post('/registro', $this->datos(['password_confirmation' => 'otra-cosa-larga']))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    // ── La verificación ─────────────────────────────────────────────────────

    public function test_sin_verificar_el_aula_esta_cerrada(): void
    {
        $user = User::factory()->student()->unverified()->create();
        Student::factory()->create(['id' => $user->getKey()]);

        $this->actingAs($user)->get('/cursos')->assertRedirect(route('verification.notice'));
    }

    public function test_el_enlace_del_correo_verifica_la_cuenta(): void
    {
        $user = User::factory()->student()->unverified()->create();
        Student::factory()->create(['id' => $user->getKey()]);

        $enlace = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->getKey(),
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($enlace)->assertRedirect(route('classroom.catalog'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    /** Sin firma no hay verificación: si no, alcanzaría con adivinar el id. */
    public function test_un_enlace_sin_firmar_no_verifica_nada(): void
    {
        $user = User::factory()->student()->unverified()->create();

        $this->actingAs($user)
            ->get('/verificar-email/'.$user->getKey().'/'.sha1($user->email))
            ->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_el_enlace_de_otro_no_sirve(): void
    {
        $user = User::factory()->student()->unverified()->create();
        $otro = User::factory()->student()->unverified()->create();

        $enlace = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $otro->getKey(),
            'hash' => sha1($otro->email),
        ]);

        $this->actingAs($user)->get($enlace)->assertForbidden();

        $this->assertNull($otro->fresh()->email_verified_at);
    }

    public function test_se_puede_pedir_otro_correo(): void
    {
        $user = User::factory()->student()->unverified()->create();

        $this->actingAs($user)->post('/verificar-email/reenviar')->assertRedirect();

        $this->assertSame(1, QueuedEmail::where('email_type', EmailType::Verification)->count());
    }

    public function test_al_ya_verificado_la_pantalla_no_le_aparece(): void
    {
        $this->actingAs(User::factory()->student()->create())
            ->get('/verificar-email')
            ->assertRedirect(route('home'));
    }

    /** Las cuentas que crea la administración entran sin pasar por esto. */
    public function test_un_alumno_verificado_entra_al_aula(): void
    {
        $user = User::factory()->student()->create();
        Student::factory()->create(['id' => $user->getKey()]);

        $this->actingAs($user)->get('/cursos')->assertSuccessful();
    }
}
