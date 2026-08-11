<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_invitado_no_entra_al_panel(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_un_alumno_no_entra_al_panel(): void
    {
        $this->actingAs(User::factory()->student()->create())
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_un_profesor_no_entra_al_panel_de_administracion(): void
    {
        $this->actingAs(User::factory()->teacher()->create())
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_un_administrador_desactivado_no_entra(): void
    {
        $this->actingAs(User::factory()->admin()->inactive()->create())
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_un_administrador_entra_al_panel(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin')
            ->assertSuccessful();
    }

    public function test_el_administrador_ve_el_listado_de_usuarios(): void
    {
        $admin = User::factory()->admin()->create();
        $alumno = User::factory()->student()->create(['first_name' => 'Carolina']);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertSuccessful()
            ->assertSee('Carolina');
    }

    public function test_el_panel_no_expone_su_propio_login(): void
    {
        // El acceso es por /login, que tiene el limitador de intentos.
        $this->get('/admin/login')->assertNotFound();
    }
}
