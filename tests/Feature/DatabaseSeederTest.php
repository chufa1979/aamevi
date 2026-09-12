<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\QueuedEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * `CourseSeeder` encola avisos reales para veinte alumnos de prueba
 * (`alumno01@aamevi.ar` … `alumno20@aamevi.ar`) que no existen: si alguna vez
 * se manda esa cola contra un SMTP real, rebotan en bloque y dañan la
 * reputación del dominio. `DatabaseSeeder` recorta la cola a dos filas por eso.
 */
class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_cola_de_email_queda_con_solo_dos_avisos(): void
    {
        $this->seed();

        $this->assertSame(2, QueuedEmail::count());
    }

    /** Un correo ya encolado antes de sembrar —de uso real— no se toca. */
    public function test_no_borra_avisos_que_ya_estaban_antes_de_sembrar(): void
    {
        $previo = QueuedEmail::factory()->create();

        $this->seed();

        $this->assertDatabaseHas('email_queue', ['id' => $previo->getKey()]);
        $this->assertSame(3, QueuedEmail::count());
    }
}
