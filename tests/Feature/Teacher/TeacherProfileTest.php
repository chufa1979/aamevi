<?php

namespace Tests\Feature\Teacher;

use Tests\TestCase;
use App\Models\User;
use Livewire\Livewire;
use App\Models\Teacher;
use Illuminate\Http\UploadedFile;
use App\Filament\Pages\TeacherProfile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * "Mi perfil" del docente: puede tocar sus propios datos, incluida la ficha
 * de profesor (specialization/bio/firma), sin pasar por el administrador.
 *
 * `Filament\Auth\Pages\EditProfile` solo lee y guarda columnas de `User`;
 * lo que se prueba acá es justamente que `TeacherProfile` la haga leer y
 * guardar también `teachers`, que la base no sabe hacer sola.
 */
class TeacherProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_precarga_la_ficha_de_profesor(): void
    {
        $teacher = Teacher::factory()->create(['specialization' => 'Nutrición clínica']);

        Livewire::actingAs($teacher->user)
            ->test(TeacherProfile::class)
            ->assertFormSet(['specialization' => 'Nutrición clínica']);
    }

    public function test_puede_actualizar_su_ficha_y_subir_la_firma(): void
    {
        $teacher = Teacher::factory()->create();
        $firma = UploadedFile::fake()->image('firma.png', 300, 100);

        Livewire::actingAs($teacher->user)
            ->test(TeacherProfile::class)
            ->fillForm([
                'first_name' => 'Ana',
                'last_name' => 'Pérez',
                'email' => $teacher->user->email,
                'specialization' => 'Cardiología del ejercicio',
                'bio' => 'Nueva biografía.',
                'signature_path' => $firma,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $teacher->refresh();
        $teacher->user->refresh();

        $this->assertSame('Ana', $teacher->user->first_name);
        $this->assertSame('Cardiología del ejercicio', $teacher->specialization);
        $this->assertSame('Nueva biografía.', $teacher->bio);
        $this->assertNotNull($teacher->signature_path);
        Storage::disk('public')->assertExists($teacher->signature_path);
    }

    public function test_un_alumno_no_puede_entrar(): void
    {
        $alumno = User::factory()->student()->create();

        $this->actingAs($alumno)
            ->get('/profesores/profile')
            ->assertForbidden();
    }
}
