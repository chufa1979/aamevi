<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\CourseModule;
use Filament\Facades\Filament;
use App\Filament\Resources\Users\UserResource;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Filament\Resources\Courses\CourseResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\Students\StudentResource;
use App\Filament\Resources\CourseClasses\CourseClassResource;
use App\Filament\Resources\CourseModules\CourseModuleResource;

/**
 * La organización del panel, calcada de la del admin de FID.
 *
 * Son tests de navegación y no de funcionalidad: protegen que las nueve
 * pantallas sigan siendo alcanzables. Un recurso que deja de registrarse, o una
 * página que se cae de `getRecordSubNavigation()`, no rompe ningún otro test
 * —simplemente desaparece del menú y nadie se entera.
 */
class CourseNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());

        Filament::setCurrentPanel('admin');
    }

    public function test_el_menu_tiene_los_cuatro_grupos_en_orden(): void
    {
        $grupos = collect(Filament::getCurrentOrDefaultPanel()->getNavigationGroups())
            ->map(fn ($grupo): string => $grupo->getLabel())
            ->values()
            ->all();

        $this->assertSame(['Cursos', 'Alumnos', 'Evaluación', 'Sistema'], $grupos);
    }

    /** @return array<string, array{class-string, string}> */
    public static function recursosDelMenu(): array
    {
        return [
            'cursos' => [CourseResource::class, 'Cursos'],
            'alumnos' => [StudentResource::class, 'Alumnos'],
            'usuarios' => [UserResource::class, 'Sistema'],
        ];
    }

    /** @param  class-string  $resource */
    #[DataProvider('recursosDelMenu')]
    public function test_cada_recurso_esta_en_su_grupo(string $resource, string $grupo): void
    {
        $this->assertSame($grupo, $resource::getNavigationGroup());
    }

    /** Cursos y Alumnos se despliegan en «Todos» y «Crear nuevo», como en FID. */
    public function test_cursos_y_alumnos_se_despliegan_en_dos_entradas(): void
    {
        foreach ([CourseResource::class, StudentResource::class] as $resource) {
            $items = collect($resource::getNavigationItems())
                ->map(fn ($item): string => $item->getLabel())
                ->all();

            $this->assertSame(['Todos', 'Crear nuevo'], $items, $resource);
        }
    }

    /** Los recursos puente no ensucian el menú, pero siguen siendo alcanzables por URL. */
    public function test_los_recursos_de_modulo_y_clase_no_estan_en_el_menu(): void
    {
        $this->assertFalse(CourseModuleResource::shouldRegisterNavigation());
        $this->assertFalse(CourseClassResource::shouldRegisterNavigation());
    }

    /** @return array<string, array{string}> */
    public static function solapasDelCurso(): array
    {
        return [
            'info general' => ['edit'],
            'planificación' => ['schedule'],
            'contenidos' => ['content'],
            'exámenes' => ['exams'],
            'alumnos del curso' => ['students'],
            'seguimiento' => ['tracking'],
        ];
    }

    #[DataProvider('solapasDelCurso')]
    public function test_cada_solapa_del_curso_abre(string $page): void
    {
        $course = Course::factory()->create();

        $this->get(CourseResource::getUrl($page, ['record' => $course]))->assertSuccessful();
    }

    public function test_las_seis_solapas_aparecen_en_la_pantalla_del_curso(): void
    {
        $course = Course::factory()->create();

        $this->get(CourseResource::getUrl('edit', ['record' => $course]))
            ->assertSuccessful()
            ->assertSee('Info general')
            ->assertSee('Planificación')
            ->assertSee('Contenidos')
            ->assertSee('Exámenes')
            ->assertSee('Alumnos del curso')
            ->assertSee('Seguimiento alumnos');
    }

    /**
     * Los retoques de estilo del panel viven en un CSS aparte, publicado por
     * `filament:assets`. Si deja de registrarse, las solapas vuelven a quedar
     * angostas y no lo nota ningún otro test.
     */
    public function test_la_hoja_de_estilos_del_panel_esta_enlazada(): void
    {
        $course = Course::factory()->create();

        $this->get(CourseResource::getUrl('edit', ['record' => $course]))
            ->assertSuccessful()
            ->assertSee('aamevi-admin.css', escape: false);
    }

    public function test_el_modulo_tiene_sus_solapas(): void
    {
        $module = CourseModule::factory()->create();

        $this->get(CourseModuleResource::getUrl('edit', ['record' => $module]))
            ->assertSuccessful()
            ->assertSee('Clases');

        $this->get(CourseModuleResource::getUrl('classes', ['record' => $module]))
            ->assertSuccessful();
    }

    public function test_la_clase_tiene_sus_solapas(): void
    {
        $class = CourseClass::factory()->create();

        $this->get(CourseClassResource::getUrl('edit', ['record' => $class]))
            ->assertSuccessful()
            ->assertSee('Autoevaluación')
            ->assertSee('Banco de preguntas');

        $this->get(CourseClassResource::getUrl('questions', ['record' => $class]))
            ->assertSuccessful();
    }
}
