<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use Livewire\Livewire;
use App\Models\Student;
use App\Models\Announcement;
use App\Models\CourseEnrollment;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\Courses\Pages\CourseAnnouncements;

/**
 * El tablón de comunicaciones del curso.
 *
 * Publicar y avisar son dos cosas: la comunicación queda siempre en el
 * tablón, y el correo sale sólo si se marca la casilla al crearla.
 */
class CourseAnnouncementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    private function cursoConAlumno(): Course
    {
        $course = Course::factory()->create();

        CourseEnrollment::factory()->approved()->create([
            'course_id' => $course->getKey(),
            'student_id' => Student::factory()->create()->getKey(),
        ]);

        return $course;
    }

    /**
     * `avisar` no es una columna de `announcements`: si vuelve a llevar
     * `dehydrated(false)`, el `after()` de la creación no la ve y esta
     * comunicación queda sin avisar aunque se haya tildado la casilla.
     */
    public function test_marcar_avisar_al_crear_encola_el_correo(): void
    {
        $course = $this->cursoConAlumno();

        Livewire::test(CourseAnnouncements::class, ['record' => $course->getKey()])
            ->callAction(TestAction::make('create')->table(), data: [
                'title' => 'Se corrió la clase del jueves',
                'body' => 'Pasa para el viernes a la misma hora.',
                'avisar' => true,
            ])
            ->assertHasNoActionErrors();

        $comunicacion = Announcement::where('title', 'Se corrió la clase del jueves')->firstOrFail();

        $this->assertNotNull($comunicacion->notified_at, 'El aviso no se encoló.');
    }

    public function test_no_marcar_avisar_no_encola_nada(): void
    {
        $course = $this->cursoConAlumno();

        Livewire::test(CourseAnnouncements::class, ['record' => $course->getKey()])
            ->callAction(TestAction::make('create')->table(), data: [
                'title' => 'Nota de color',
                'body' => 'Sin urgencia.',
                'avisar' => false,
            ])
            ->assertHasNoActionErrors();

        $comunicacion = Announcement::where('title', 'Nota de color')->firstOrFail();

        $this->assertNull($comunicacion->notified_at);
    }
}
