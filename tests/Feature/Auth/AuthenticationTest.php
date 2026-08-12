<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
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
        $user = User::factory()->create([
            'email' => 'test@aamevi.ar',
            'password' => 'password',
        ]);

        $this->post('/login', [
            'email' => 'test@aamevi.ar',
            'password' => 'password',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
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

    public function test_un_usuario_autenticado_no_ve_el_login(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/login')
            ->assertRedirect('/');
    }
}
