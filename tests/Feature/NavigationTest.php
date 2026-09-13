<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * El menú del sitio ofrece sólo lo que cada uno puede abrir.
 *
 * Casi todo el menú es del aula, y el aula es de los alumnos: sin recorte, un
 * administrador veía «Cursos», «Mi progreso» y «Certificados», y las tres le
 * daban 403. Un menú con puertas que rebotan es peor que un menú corto.
 */
class NavigationTest extends TestCase
{
    use RefreshDatabase;

    private function alumno(): User
    {
        $user = User::factory()->student()->create();
        Student::factory()->create(['id' => $user->getKey()]);

        return $user;
    }

    public function test_el_alumno_ve_las_secciones_del_aula(): void
    {
        $this->actingAs($this->alumno())
            ->get('/')
            ->assertSuccessful()
            ->assertSee('Mi progreso')
            ->assertSee('Certificados');
    }

    public function test_el_administrador_no_ve_secciones_que_le_darian_403(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())->get('/');

        $response->assertSuccessful();
        $response->assertDontSee('Mi progreso');
        $response->assertDontSee('Certificados');
    }

    public function test_el_docente_tampoco(): void
    {
        $response = $this->actingAs(Teacher::factory()->create()->user)->get('/');

        $response->assertSuccessful();
        $response->assertDontSee('Mi progreso');
        $response->assertDontSee('Certificados');
    }

    /** Sacadas las secciones del aula, el menú tiene que llevar a algún lado. */
    public function test_el_menu_ofrece_el_panel_a_quien_tiene_uno(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/')
            ->assertSee('Administración');

        $this->actingAs(Teacher::factory()->create()->user)
            ->get('/')
            ->assertSee('/profesores', escape: false);
    }

    public function test_al_alumno_no_se_le_ofrece_ningun_panel(): void
    {
        $this->actingAs($this->alumno())
            ->get('/')
            ->assertDontSee('/profesores', escape: false)
            ->assertDontSee('Administración');
    }

    /**
     * El sitio está detrás de `auth`: en la portada nunca hay un invitado.
     *
     * La banda de cierre invitaba a crear una cuenta que quien la lee ya tiene,
     * y a iniciar una sesión que ya está iniciada.
     */
    public function test_la_portada_no_invita_a_crear_una_cuenta(): void
    {
        foreach ([$this->alumno(), User::factory()->admin()->create(), Teacher::factory()->create()->user] as $user) {
            $this->actingAs($user)->get('/')
                ->assertDontSee('Quiero unirme')
                ->assertDontSee('Ya tengo cuenta');
        }
    }

    public function test_al_alumno_la_portada_lo_lleva_al_catalogo(): void
    {
        $this->actingAs($this->alumno())->get('/')->assertSee('Ver el catálogo');
    }

    /*
     * La ayuda es de todos, pero no vive en un solo lugar: el menú horizontal
     * de la portada desaparece apenas se entra a usar la plataforma —adentro
     * del aula manda `x-classroom.nav`, adentro de un panel manda Filament—,
     * así que cada rol la tiene en la superficie donde de verdad vive.
     *
     * Un método por rol y no un foreach/secuencia en uno solo: Filament deja
     * estado estático del panel actual entre requests simuladas dentro del
     * mismo test, y mezclar dos paneles en el mismo método da falsos
     * negativos.
     */
    public function test_el_alumno_ve_la_ayuda_en_el_aula(): void
    {
        $this->actingAs($this->alumno())->get('/mis-cursos')->assertSee('Ayuda');
    }

    public function test_el_administrador_ve_la_ayuda_en_su_panel(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertSee('Ayuda');
    }

    public function test_el_docente_ve_la_ayuda_en_su_panel(): void
    {
        $this->actingAs(Teacher::factory()->create()->user)->get('/profesores/courses')->assertSee('Ayuda');
    }

    public function test_el_administrativo_ve_la_ayuda_en_su_panel(): void
    {
        $this->actingAs(User::factory()->registrar()->create())->get('/administracion/solicitudes')->assertSee('Ayuda');
    }
}
