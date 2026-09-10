<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\CourseModule;
use App\Enums\CourseModality;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * La vidriera pública: home y ficha de curso, ambas sin sesión.
 *
 * `/` y `/curso/{course}` son la única excepción deliberada a «nada es
 * accesible sin sesión iniciada» (ver AuthenticationTest). El resto de la
 * plataforma sigue detrás de auth.
 */
class PublicShowcaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_home_publico_muestra_los_cursos_activos(): void
    {
        $activo = Course::factory()->create(['title' => 'Curso activo visible']);
        Course::factory()->inactive()->create(['title' => 'Curso inactivo oculto']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Curso activo visible');
        $response->assertDontSee('Curso inactivo oculto');
    }

    public function test_un_usuario_con_sesion_sigue_viendo_su_dashboard_en_home(): void
    {
        $response = $this->actingAs(User::factory()->student()->create())->get('/');

        $response->assertOk();
        $response->assertViewIs('home');
    }

    public function test_la_ficha_publica_de_un_curso_activo_es_visible_sin_sesion(): void
    {
        $course = Course::factory()->create(['title' => 'Diplomatura de prueba']);

        $response = $this->get(route('course.showcase', $course));

        $response->assertOk();
        $response->assertSee('Diplomatura de prueba');
    }

    public function test_un_curso_inactivo_no_tiene_ficha_publica(): void
    {
        $course = Course::factory()->inactive()->create();

        $this->get(route('course.showcase', $course))->assertNotFound();
    }

    /**
     * Todos los datos «tipo AMA» que el CRUD de admin permite cargar tienen
     * que verse en la ficha: fecha, días, horario, lugar, especialidades,
     * modalidad, inversión, objetivos, cuerpo docente, requisitos y
     * certificación.
     */
    public function test_la_ficha_publica_muestra_los_datos_completos_del_curso(): void
    {
        $course = Course::factory()->create([
            'start_date' => '2026-05-07',
            'end_date' => '2026-09-24',
            'modality' => CourseModality::Online,
            'location' => 'Asociación Médica Argentina - Campus Virtual AMA',
            'specialties' => 'Medicina General, Medicina del Estilo de Vida',
            'schedule_days' => 'Clases sincrónicas: jueves',
            'schedule_time' => 'Sincrónicas: 19:30 hs.',
            'investment_info' => '5 cuotas fijas mensuales sin interés de $190.400 c/u.',
            'certification_info' => 'Certifica AAMEVi con aval académico.',
            'teaching_staff' => "Dra. Ana Pérez — Nutrición\nDr. Juan Gómez — Actividad física",
            'objectives' => "Comprender los pilares de la medicina del estilo de vida\nAplicar estrategias de cambio de comportamiento",
            'enrollment_requirements' => "Título profesional\nFotocopia de DNI",
        ]);

        $course->teacher->update([
            'bio' => 'Director académico de EGAMA.',
            'specialization' => 'Medicina del Estilo de Vida',
        ]);

        CourseModule::factory()->for($course)->create(['title' => 'Fundamentos del cambio de hábitos']);

        $response = $this->get(route('course.showcase', $course));

        $response->assertOk();
        $response->assertSee('07/05/2026');
        $response->assertSee('24/09/2026');
        $response->assertSee('Asociación Médica Argentina - Campus Virtual AMA');
        $response->assertSee('Medicina General, Medicina del Estilo de Vida');
        $response->assertSee('Clases sincrónicas: jueves');
        $response->assertSee('Sincrónicas: 19:30 hs.');
        $response->assertSee('Online');
        $response->assertSee('190.400', false);
        $response->assertSee('Certifica AAMEVi con aval académico.');
        $response->assertSee('Director del curso');
        $response->assertSee('Director académico de EGAMA.');
        $response->assertSee('Cuerpo docente');
        $response->assertSee('Dra. Ana Pérez — Nutrición');
        $response->assertSee('Programa del curso');
        $response->assertSee('Objetivos del curso');
        $response->assertSee('Comprender los pilares de la medicina del estilo de vida');
        $response->assertSee('Inscripción e Informes');
        $response->assertSee('Título profesional');
    }

    /** Un curso recién creado, sin ninguno de esos datos cargados, no rompe la ficha. */
    public function test_la_ficha_publica_no_falla_con_datos_incompletos(): void
    {
        $course = Course::factory()->create([
            'start_date' => null,
            'end_date' => null,
            'location' => null,
            'specialties' => null,
            'schedule_days' => null,
            'schedule_time' => null,
            'investment_info' => null,
            'certification_info' => null,
            'teaching_staff' => null,
            'objectives' => null,
            'enrollment_requirements' => null,
        ]);

        $this->get(route('course.showcase', $course))->assertOk();
    }

    public function test_desde_la_ficha_publica_un_invitado_vuelve_ahi_tras_loguearse(): void
    {
        $course = Course::factory()->create();
        $user = User::factory()->student()->create(['email' => 'test@aamevi.ar', 'password' => 'password']);

        $this->post('/login', [
            'email' => 'test@aamevi.ar',
            'password' => 'password',
            'next' => route('course.showcase', $course),
        ])->assertRedirect(route('course.showcase', $course));

        $this->assertAuthenticatedAs($user);
    }

    /** Un destino de `next` fuera del sitio no se respeta: sería una redirección abierta. */
    public function test_un_next_externo_no_se_respeta(): void
    {
        User::factory()->student()->create(['email' => 'test@aamevi.ar', 'password' => 'password']);

        $this->post('/login', [
            'email' => 'test@aamevi.ar',
            'password' => 'password',
            'next' => 'https://evil.example/',
        ])->assertRedirect('/mis-cursos');
    }
}
