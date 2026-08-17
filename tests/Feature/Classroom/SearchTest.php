<?php

namespace Tests\Feature\Classroom;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\CourseClass;
use App\Models\CourseModule;
use App\Services\SearchService;
use App\Models\CourseEnrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * El buscador del encabezado.
 *
 * Lo que más importa acá no es que encuentre sino que **no** encuentre: busca
 * únicamente lo que ese alumno puede abrir. La lista de títulos que existen ya
 * es información, así que filtrar después de buscar no alcanzaría.
 */
class SearchTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = Student::factory()->create();
    }

    /** Un curso con una clase, y al alumno inscripto si se pide. */
    private function curso(string $titulo, string $clase, bool $inscripto = true, bool $dictada = true): Course
    {
        $course = Course::factory()->create(['title' => $titulo]);
        $module = CourseModule::factory()->for($course)->create(['order_number' => 1]);

        CourseClass::factory()->for($module, 'module')->create([
            'title' => $clase,
            'order_number' => 1,
            'activation_date' => $dictada ? now()->subWeek() : now()->addMonth(),
        ]);

        if ($inscripto) {
            CourseEnrollment::factory()->approved()->create([
                'course_id' => $course->getKey(),
                'student_id' => $this->student->getKey(),
            ]);
        }

        return $course->fresh();
    }

    private function buscar(string $q)
    {
        return $this->actingAs($this->student->user)->get('/buscar?q='.urlencode($q));
    }

    // ── Lo que encuentra ────────────────────────────────────────────────────

    public function test_encuentra_un_curso_que_cursa(): void
    {
        $this->curso('Sueño, estrés y salud mental', 'La higiene del sueño');

        $this->buscar('sueño')->assertSuccessful()->assertSee('Sueño, estrés y salud mental');
    }

    public function test_encuentra_una_clase_de_un_curso_suyo(): void
    {
        $this->curso('Nutrición aplicada', 'Patrones alimentarios');

        $this->buscar('patrones')->assertSuccessful()->assertSee('Patrones alimentarios');
    }

    /** Un curso abierto al que todavía no se anotó también le sirve. */
    public function test_encuentra_un_curso_del_catalogo(): void
    {
        $this->curso('Actividad física y ejercicio', 'Prescripción con dosis', inscripto: false);

        $this->buscar('actividad')->assertSuccessful()->assertSee('Actividad física y ejercicio');
    }

    /** Buscar por partes: sin esto «medicina vida» no encontraría nada. */
    public function test_las_palabras_se_buscan_por_separado(): void
    {
        $this->curso('Fundamentos de la Medicina del Estilo de Vida', 'Los seis pilares');

        $this->buscar('medicina vida')->assertSee('Fundamentos de la Medicina del Estilo de Vida');
    }

    // ── Lo que no encuentra ─────────────────────────────────────────────────

    /** Las clases de un curso ajeno no existen para él, aunque el curso se ofrezca. */
    public function test_no_encuentra_clases_de_un_curso_ajeno(): void
    {
        $this->curso('Curso ajeno', 'Clase reservada', inscripto: false);

        $this->buscar('reservada')->assertSuccessful()->assertDontSee('Clase reservada');
    }

    public function test_no_encuentra_un_curso_inactivo_al_que_no_esta_inscripto(): void
    {
        $course = $this->curso('Edición cerrada', 'Clase vieja', inscripto: false);
        $course->update(['is_active' => false]);

        $this->buscar('cerrada')->assertSuccessful()->assertDontSee('Edición cerrada');
    }

    public function test_con_una_sola_letra_no_busca(): void
    {
        $this->curso('Sueño, estrés y salud mental', 'La higiene del sueño');

        $this->buscar('s')->assertSuccessful()
            ->assertSee('al menos '.SearchService::MINIMO.' letras')
            ->assertDontSee('Sueño, estrés y salud mental');
    }

    public function test_sin_termino_no_muestra_resultados(): void
    {
        $this->curso('Sueño, estrés y salud mental', 'La higiene del sueño');

        $this->actingAs($this->student->user)->get('/buscar')
            ->assertSuccessful()
            ->assertDontSee('Sueño, estrés y salud mental');
    }

    public function test_avisa_cuando_no_hay_nada(): void
    {
        $this->buscar('homeopatía')->assertSuccessful()->assertSee('No encontramos nada');
    }

    // ── Clases que no puede abrir ───────────────────────────────────────────

    /**
     * Una clase futura figura en el temario desde el primer día, así que
     * aparece; pero sin enlace, porque llevaría a un 403.
     */
    public function test_una_clase_que_no_puede_abrir_aparece_sin_enlace(): void
    {
        $course = $this->curso('Nutrición aplicada', 'Clase por venir', dictada: false);
        $class = $course->classes()->firstOrFail();

        $response = $this->buscar('por venir');

        $response->assertSee('Clase por venir');
        $response->assertDontSee(route('classroom.class', $class), escape: false);
        $response->assertSee(route('classroom.course', $course), escape: false);
    }

    // ── Quién lo ve ─────────────────────────────────────────────────────────

    public function test_el_buscador_esta_en_el_encabezado_del_alumno(): void
    {
        $this->actingAs($this->student->user)->get('/')->assertSee('Buscar en la plataforma');
    }

    /** Y adentro del aula, que es donde el alumno pasa el día. */
    public function test_el_buscador_esta_tambien_en_el_aula(): void
    {
        $this->actingAs($this->student->user)
            ->get('/mis-cursos')
            ->assertSee('Buscar en la plataforma');
    }

    /** El docente y el administrador buscan desde su panel. */
    public function test_no_aparece_para_quien_no_es_alumno(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/')
            ->assertDontSee('Buscar en la plataforma');

        $this->actingAs(Teacher::factory()->create()->user)
            ->get('/')
            ->assertDontSee('Buscar en la plataforma');
    }

    public function test_un_docente_no_entra_al_buscador(): void
    {
        $this->actingAs(Teacher::factory()->create()->user)->get('/buscar?q=algo')->assertForbidden();
    }
}
