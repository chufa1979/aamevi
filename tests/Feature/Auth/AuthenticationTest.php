<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('test@aamevi.ar|127.0.0.1');
    }

    public function test_el_invitado_es_redirigido_al_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    /**
     * Ninguna sección de la plataforma puede verse sin sesión: es el requisito
     * central de este módulo.
     */
    public function test_ninguna_seccion_es_accesible_sin_sesion(): void
    {
        foreach (['/', '/cursos', '/mis-cursos', '/progreso', '/certificados', '/ayuda', '/buscar'] as $ruta) {
            $this->get($ruta)->assertRedirect('/login');
        }
    }

    public function test_el_login_no_muestra_la_navegacion_de_la_plataforma(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Iniciar sesión');

        // El menú del sitio no debe aparecer para quien no inició sesión
        foreach (['Mi progreso', 'Certificados', 'Mis cursos'] as $item) {
            $response->assertDontSee($item);
        }
    }

    public function test_un_usuario_puede_iniciar_sesion(): void
    {
        $user = User::factory()->student()->create([
            'email' => 'test@aamevi.ar',
            'password' => 'password',
        ]);

        $this->post('/login', [
            'email' => 'test@aamevi.ar',
            'password' => 'password',
        ])->assertRedirect(route('classroom.courses'));

        $this->assertAuthenticatedAs($user);
    }

    /**
     * Cada rol entra a lo suyo.
     *
     * La portada no es la pantalla de trabajo de ninguno: al alumno le sirve
     * ver sus cursos, y al docente o al administrador su panel.
     */
    #[DataProvider('destinos')]
    public function test_cada_rol_entra_a_su_pantalla(string $rol, string $destino): void
    {
        User::factory()->{$rol}()->create(['email' => 'test@aamevi.ar', 'password' => 'password']);

        $this->post('/login', ['email' => 'test@aamevi.ar', 'password' => 'password'])
            ->assertRedirect($destino);
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function destinos(): array
    {
        return [
            'alumno' => ['student', '/mis-cursos'],
            'profesor' => ['teacher', '/profesores'],
            'administrador' => ['admin', '/admin'],
        ];
    }

    /**
     * Si venía de algún lado, vuelve ahí.
     *
     * Pedirle la contraseña en el camino y después soltarlo en otra pantalla le
     * hace perder lo que estaba por abrir.
     */
    public function test_vuelve_a_la_pantalla_que_estaba_buscando(): void
    {
        User::factory()->student()->create(['email' => 'test@aamevi.ar', 'password' => 'password']);

        $this->get('/progreso')->assertRedirect('/login');

        $this->post('/login', ['email' => 'test@aamevi.ar', 'password' => 'password'])
            ->assertRedirect('/progreso');
    }

    public function test_no_se_puede_iniciar_sesion_con_la_contrasena_incorrecta(): void
    {
        User::factory()->create(['email' => 'test@aamevi.ar', 'password' => 'password']);

        $this->post('/login', [
            'email' => 'test@aamevi.ar',
            'password' => 'incorrecta',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_una_cuenta_desactivada_no_puede_entrar(): void
    {
        User::factory()->inactive()->create([
            'email' => 'test@aamevi.ar',
            'password' => 'password',
        ]);

        $this->post('/login', [
            'email' => 'test@aamevi.ar',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_una_cuenta_de_google_sin_contrasena_no_entra_por_formulario(): void
    {
        User::factory()->fromGoogle()->create(['email' => 'test@aamevi.ar']);

        $this->post('/login', [
            'email' => 'test@aamevi.ar',
            'password' => 'loquesea',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_el_login_se_bloquea_tras_cinco_intentos_fallidos(): void
    {
        User::factory()->create(['email' => 'test@aamevi.ar', 'password' => 'password']);

        foreach (range(1, 5) as $intento) {
            $this->post('/login', ['email' => 'test@aamevi.ar', 'password' => 'incorrecta']);
        }

        // El sexto intento falla aunque la contraseña sea la correcta
        $this->post('/login', [
            'email' => 'test@aamevi.ar',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_un_usuario_autenticado_puede_cerrar_sesion(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    /** Abrir /login con la sesión iniciada lleva a lo de uno, no a la portada. */
    #[DataProvider('destinos')]
    public function test_el_login_con_sesion_iniciada_redirige_a_lo_suyo(string $rol, string $destino): void
    {
        $this->actingAs(User::factory()->{$rol}()->create())
            ->get('/login')
            ->assertRedirect($destino);
    }
}
