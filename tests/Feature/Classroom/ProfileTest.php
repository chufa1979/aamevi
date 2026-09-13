<?php

namespace Tests\Feature\Classroom;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * "Mi perfil" del alumno: puede tocar su propia ficha, sin pasar por el
 * administrador. Sin email ni contraseña — eso es la cuenta, no la ficha.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function alumno(): User
    {
        $user = User::factory()->student()->create();
        Student::factory()->create(['id' => $user->getKey()]);

        return $user;
    }

    public function test_el_alumno_ve_su_perfil_precargado(): void
    {
        $user = $this->alumno();
        $user->student->update(['dni' => '30111222', 'city' => 'Rosario']);

        $this->actingAs($user)
            ->get('/perfil')
            ->assertSuccessful()
            ->assertSee('30111222')
            ->assertSee('Rosario');
    }

    public function test_puede_actualizar_su_ficha(): void
    {
        $user = $this->alumno();

        $this->actingAs($user)
            ->put('/perfil', [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => '+54 9 341 555 1234',
                'city' => 'Rosario',
                'university' => 'Universidad Nacional de Rosario',
            ])
            ->assertRedirect()
            ->assertSessionHas('exito');

        $user->refresh();
        $this->assertSame('+54 9 341 555 1234', $user->student->phone);
        $this->assertSame('Rosario', $user->student->city);
        $this->assertSame('Universidad Nacional de Rosario', $user->student->university);
    }

    public function test_no_puede_dejar_el_nombre_vacio(): void
    {
        $user = $this->alumno();

        $this->actingAs($user)
            ->put('/perfil', ['first_name' => '', 'last_name' => $user->last_name])
            ->assertSessionHasErrors('first_name');
    }

    /** Mismo criterio que el registro: dos alumnos no pueden compartir DNI. */
    public function test_no_puede_usar_el_dni_de_otro_alumno(): void
    {
        $otro = $this->alumno();
        $otro->student->update(['dni' => '30111222']);

        $user = $this->alumno();

        $this->actingAs($user)
            ->put('/perfil', [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'dni' => '30111222',
            ])
            ->assertSessionHasErrors('dni');
    }

    /** Guardar el propio DNI sin cambiarlo no debe chocar contra sí mismo. */
    public function test_puede_guardar_sin_tocar_su_propio_dni(): void
    {
        $user = $this->alumno();
        $user->student->update(['dni' => '30111222']);

        $this->actingAs($user)
            ->put('/perfil', [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'dni' => '30111222',
                'city' => 'La Plata',
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('La Plata', $user->student->fresh()->city);
    }

    public function test_un_docente_no_puede_entrar(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get('/perfil')->assertForbidden();
    }
}
