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

    /** La ayuda es de todos: no depende del rol. */
    public function test_la_ayuda_la_ven_todos(): void
    {
        foreach ([$this->alumno(), User::factory()->admin()->create(), Teacher::factory()->create()->user] as $user) {
            $this->actingAs($user)->get('/')->assertSee('Ayuda');
        }
    }
}
