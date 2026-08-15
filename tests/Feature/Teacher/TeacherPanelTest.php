<?php

namespace Tests\Feature\Teacher;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use Livewire\Livewire;
use App\Models\Teacher;
use App\Models\Question;
use App\Models\CourseClass;
use App\Models\CourseModule;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\Courses\Pages\EditCourse;

/**
 * El panel del docente.
 *
 * Comparte los recursos con /admin, así que lo que hay que probar no es que las
 * pantallas existan —son las mismas— sino que el recorte aguante: que un docente
 * no vea ni pueda tocar lo que no es suyo, aunque escriba la URL a mano.
 */
class TeacherPanelTest extends TestCase
{
    use RefreshDatabase;

    /** Un docente con un curso propio. */
    private function docenteCon(string $titulo): Teacher
    {
        $teacher = Teacher::factory()->create();

        Course::factory()->for($teacher)->create(['title' => $titulo]);

        return $teacher;
    }

    private function claseDe(Course $course): CourseClass
    {
        $module = CourseModule::factory()->for($course)->create(['order_number' => 1]);

        return CourseClass::factory()->for($module, 'module')->create(['order_number' => 1]);
    }

    public function test_el_invitado_no_entra(): void
    {
        $this->get('/profesores')->assertRedirect('/login');
    }

    public function test_un_alumno_no_entra(): void
    {
        $this->actingAs(User::factory()->student()->create())
            ->get('/profesores')
            ->assertForbidden();
    }

    /** El panel de administración es un superconjunto: no hace falta la otra puerta. */
    public function test_un_administrador_no_entra_al_panel_de_profesores(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/profesores')
            ->assertForbidden();
    }

    public function test_un_docente_desactivado_no_entra(): void
    {
        $teacher = Teacher::factory()->create();
        $teacher->user->update(['is_active' => false]);

        $this->actingAs($teacher->user)->get('/profesores')->assertForbidden();
    }

    /** No hay tablero: se entra directo a los cursos, que es con lo que se trabaja. */
    public function test_un_docente_entra_y_cae_en_sus_cursos(): void
    {
        $teacher = $this->docenteCon('Medicina del estilo de vida');

        $this->actingAs($teacher->user)
            ->get('/profesores')
            ->assertRedirect('/profesores/courses');

        $this->actingAs($teacher->user)
            ->get('/profesores/courses')
            ->assertSuccessful()
            ->assertSee('Medicina del estilo de vida');
    }

    public function test_el_listado_no_muestra_los_cursos_de_otro_docente(): void
    {
        $teacher = $this->docenteCon('Nutrición aplicada');
        $this->docenteCon('Sueño y descanso');

        $this->actingAs($teacher->user)
            ->get('/profesores/courses')
            ->assertSuccessful()
            ->assertSee('Nutrición aplicada')
            ->assertDontSee('Sueño y descanso');
    }

    /** Escribir la URL del curso ajeno no alcanza: la consulta del recurso no lo trae. */
    public function test_no_puede_abrir_el_curso_de_otro_docente(): void
    {
        $teacher = $this->docenteCon('Nutrición aplicada');
        $ajeno = $this->docenteCon('Sueño y descanso')->courses()->firstOrFail();

        $this->actingAs($teacher->user)
            ->get("/profesores/courses/{$ajeno->getKey()}/edit")
            ->assertNotFound();
    }

    public function test_puede_abrir_el_propio(): void
    {
        $teacher = $this->docenteCon('Nutrición aplicada');
        $propio = $teacher->courses()->firstOrFail();

        $this->actingAs($teacher->user)
            ->get("/profesores/courses/{$propio->getKey()}/edit")
            ->assertSuccessful();
    }

    /**
     * Las solapas del curso son las mismas que en /admin. Se prueban todas
     * porque cada una resuelve el registro por su cuenta: alcanza con que una
     * se saltee la consulta del recurso para abrir el curso de otro.
     */
    public function test_abre_todas_las_solapas_de_su_curso(): void
    {
        $teacher = $this->docenteCon('Nutrición aplicada');
        $curso = $teacher->courses()->firstOrFail();

        $this->actingAs($teacher->user);

        foreach (['edit', 'planificacion', 'contenidos', 'examenes', 'intentos', 'calificaciones', 'alumnos', 'seguimiento'] as $solapa) {
            $this->get("/profesores/courses/{$curso->getKey()}/{$solapa}")
                ->assertSuccessful();
        }
    }

    public function test_ninguna_solapa_abre_el_curso_de_otro(): void
    {
        $teacher = $this->docenteCon('Nutrición aplicada');
        $ajeno = $this->docenteCon('Sueño y descanso')->courses()->firstOrFail();

        $this->actingAs($teacher->user);

        foreach (['edit', 'planificacion', 'contenidos', 'examenes', 'intentos', 'calificaciones', 'alumnos', 'seguimiento'] as $solapa) {
            $this->get("/profesores/courses/{$ajeno->getKey()}/{$solapa}")
                ->assertNotFound();
        }
    }

    /** El árbol sigue hacia abajo: módulos y clases también son de alguien. */
    public function test_no_puede_abrir_el_modulo_ni_la_clase_de_otro(): void
    {
        $teacher = $this->docenteCon('Nutrición aplicada');
        $ajena = $this->claseDe($this->docenteCon('Sueño y descanso')->courses()->firstOrFail());

        $this->actingAs($teacher->user);

        $this->get("/profesores/course-modules/{$ajena->module_id}/clases")->assertNotFound();
        $this->get("/profesores/course-classes/{$ajena->getKey()}/preguntas")->assertNotFound();
    }

    /** El alta de cursos es del administrador: elegir docente no es cosa del docente. */
    public function test_no_puede_crear_cursos(): void
    {
        $teacher = $this->docenteCon('Nutrición aplicada');

        $this->actingAs($teacher->user)
            ->get('/profesores/courses/create')
            ->assertForbidden();
    }

    /**
     * El campo Docente se muestra deshabilitado, y Filament no manda al servidor
     * lo que está deshabilitado: aunque se fuerce el valor en el navegador, el
     * curso sigue siendo de quien era.
     */
    public function test_no_puede_reasignar_su_curso_a_otro_docente(): void
    {
        $teacher = $this->docenteCon('Nutrición aplicada');
        $curso = $teacher->courses()->firstOrFail();
        $otro = Teacher::factory()->create();

        $this->actingAs($teacher->user);
        Filament::setCurrentPanel('profesores');

        Livewire::test(EditCourse::class, ['record' => $curso->getKey()])
            ->fillForm(['teacher_id' => $otro->getKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($teacher->getKey(), $curso->fresh()->teacher_id);
    }

    public function test_puede_editar_lo_suyo(): void
    {
        $teacher = $this->docenteCon('Nutrición aplicada');
        $curso = $teacher->courses()->firstOrFail();

        $this->actingAs($teacher->user);
        Filament::setCurrentPanel('profesores');

        Livewire::test(EditCourse::class, ['record' => $curso->getKey()])
            ->fillForm(['title' => 'Nutrición aplicada II'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Nutrición aplicada II', $curso->fresh()->title);
    }

    public function test_el_banco_solo_trae_las_preguntas_de_sus_cursos(): void
    {
        $teacher = $this->docenteCon('Nutrición aplicada');

        Question::factory()->create([
            'class_id' => $this->claseDe($teacher->courses()->firstOrFail())->getKey(),
            'text' => '<p>¿Cuántas porciones de fruta por día?</p>',
        ]);

        Question::factory()->create([
            'class_id' => $this->claseDe($this->docenteCon('Sueño y descanso')->courses()->firstOrFail())->getKey(),
            'text' => '<p>¿Cuántas horas de sueño profundo?</p>',
        ]);

        $this->actingAs($teacher->user)
            ->get('/profesores/questions')
            ->assertSuccessful()
            ->assertSee('porciones de fruta')
            ->assertDontSee('sueño profundo');
    }

    /** Las cuentas son del administrador; el docente ve a sus alumnos desde el curso. */
    public function test_el_panel_no_tiene_pantalla_de_usuarios(): void
    {
        $teacher = $this->docenteCon('Nutrición aplicada');

        $this->actingAs($teacher->user)->get('/profesores/users')->assertNotFound();
        $this->actingAs($teacher->user)->get('/profesores/alumnos')->assertNotFound();
    }

    public function test_el_panel_no_expone_su_propio_login(): void
    {
        $this->get('/profesores/login')->assertNotFound();
    }

    /** El recorte no le puede haber achicado la vista al administrador. */
    public function test_el_administrador_sigue_viendo_todos_los_cursos(): void
    {
        $this->docenteCon('Nutrición aplicada');
        $this->docenteCon('Sueño y descanso');

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/courses')
            ->assertSuccessful()
            ->assertSee('Nutrición aplicada')
            ->assertSee('Sueño y descanso');
    }
}
