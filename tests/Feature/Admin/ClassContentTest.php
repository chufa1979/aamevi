<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use Livewire\Livewire;
use App\Models\CourseClass;
use App\Models\ClassContent;
use App\Models\CourseModule;
use App\Enums\ClassContentType;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\CourseModules\Pages\EditCourseModule;
use App\Filament\Resources\CourseModules\RelationManagers\ClassesRelationManager;

class ClassContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_se_puede_crear_una_clase_con_su_contenido(): void
    {
        $module = CourseModule::factory()->create();

        Livewire::test(ClassesRelationManager::class, [
            'ownerRecord' => $module,
            'pageClass' => EditCourseModule::class,
        ])
            ->callAction(TestAction::make('create')->table(), data: [
                'title' => 'Clase 1',
                'order_number' => 1,
                'activation_date' => now()->format('Y-m-d H:i:s'),
                'is_live_session' => false,
                'contents' => [
                    [
                        'type' => ClassContentType::Video->value,
                        'title' => 'Introducción en video',
                        'content_url' => 'https://www.youtube.com/watch?v=abc123',
                    ],
                    [
                        'type' => ClassContentType::Text->value,
                        'title' => 'Lectura complementaria',
                        'description' => 'Cuerpo del texto.',
                    ],
                ],
            ])
            ->assertHasNoActionErrors();

        $class = CourseClass::where('title', 'Clase 1')->firstOrFail();

        $this->assertCount(2, $class->contents);
        $this->assertSame(ClassContentType::Video, $class->contents[0]->type);
        $this->assertSame('https://www.youtube.com/watch?v=abc123', $class->contents[0]->content_url);
        $this->assertSame(ClassContentType::Text, $class->contents[1]->type);
    }

    public function test_el_contenido_conserva_el_orden_del_repeater(): void
    {
        $module = CourseModule::factory()->create();

        Livewire::test(ClassesRelationManager::class, [
            'ownerRecord' => $module,
            'pageClass' => EditCourseModule::class,
        ])
            ->callAction(TestAction::make('create')->table(), data: [
                'title' => 'Clase ordenada',
                'order_number' => 1,
                'activation_date' => now()->format('Y-m-d H:i:s'),
                'is_live_session' => false,
                'contents' => [
                    ['type' => ClassContentType::Text->value, 'title' => 'Primero', 'description' => 'a'],
                    ['type' => ClassContentType::Text->value, 'title' => 'Segundo', 'description' => 'b'],
                    ['type' => ClassContentType::Text->value, 'title' => 'Tercero', 'description' => 'c'],
                ],
            ])
            ->assertHasNoActionErrors();

        $titulos = CourseClass::where('title', 'Clase ordenada')
            ->firstOrFail()
            ->contents
            ->pluck('title')
            ->all();

        $this->assertSame(['Primero', 'Segundo', 'Tercero'], $titulos);
    }

    public function test_el_texto_enriquecido_conserva_el_formato(): void
    {
        $module = CourseModule::factory()->create();
        $html = '<p>Consigna con <strong>negrita</strong> y una <em>lista</em>:</p><ul><li>Uno</li><li>Dos</li></ul>';

        Livewire::test(ClassesRelationManager::class, [
            'ownerRecord' => $module,
            'pageClass' => EditCourseModule::class,
        ])
            ->callAction(TestAction::make('create')->table(), data: [
                'title' => 'Clase con tarea',
                'order_number' => 1,
                'activation_date' => now()->format('Y-m-d H:i:s'),
                'is_live_session' => false,
                'contents' => [
                    [
                        'type' => ClassContentType::Task->value,
                        'title' => 'Trabajo práctico',
                        'description' => $html,
                    ],
                ],
            ])
            ->assertHasNoActionErrors();

        $contenido = CourseClass::where('title', 'Clase con tarea')->firstOrFail()->contents->first();

        $this->assertSame(ClassContentType::Task, $contenido->type);

        // El editor normaliza el marcado —envuelve el contenido de cada <li> en
        // un <p>—, así que se verifica que el formato sobreviva, no que el HTML
        // sea idéntico al que se envió.
        $this->assertStringContainsString('<strong>negrita</strong>', $contenido->description);
        $this->assertStringContainsString('<em>lista</em>', $contenido->description);
        $this->assertStringContainsString('<ul>', $contenido->description);
        $this->assertStringContainsString('Uno', $contenido->description);
        $this->assertStringContainsString('Dos', $contenido->description);
    }

    /**
     * Al cambiar de tipo, los datos del anterior no pueden quedar colgados: un
     * contenido que pasó de video a texto conservaría el enlace de YouTube.
     */
    public function test_cambiar_el_tipo_limpia_los_datos_del_anterior(): void
    {
        $class = CourseClass::factory()->create();
        $video = ClassContent::factory()->video()->for($class, 'class')->create(['order_number' => 1]);

        $this->assertNotNull($video->content_url);

        Livewire::test(ClassesRelationManager::class, [
            'ownerRecord' => $class->module,
            'pageClass' => EditCourseModule::class,
        ])
            ->callAction(TestAction::make('edit')->table($class), data: [
                'title' => $class->title,
                'order_number' => $class->order_number,
                'activation_date' => $class->activation_date->format('Y-m-d H:i:s'),
                'is_live_session' => false,
                'contents' => [
                    [
                        'type' => ClassContentType::Text->value,
                        'title' => 'Ahora es texto',
                        'description' => '<p>Cuerpo</p>',
                    ],
                ],
            ])
            ->assertHasNoActionErrors();

        $contenido = $class->refresh()->contents->first();

        $this->assertSame(ClassContentType::Text, $contenido->type);
        $this->assertNull($contenido->content_url, 'Quedó colgado el enlace del video anterior.');
        $this->assertSame('<p>Cuerpo</p>', $contenido->description);
    }

    public function test_borrar_la_clase_arrastra_su_contenido(): void
    {
        $content = ClassContent::factory()->create();
        $class = $content->class;

        $class->delete();

        $this->assertDatabaseMissing('class_content', ['id' => $content->id]);
    }

    public function test_url_devuelve_el_enlace_externo_tal_cual(): void
    {
        $video = ClassContent::factory()->video()->create();

        $this->assertSame($video->content_url, $video->url());
    }

    public function test_url_resuelve_los_archivos_del_disco_publico(): void
    {
        $pdf = ClassContent::factory()->pdf()->create();

        $url = $pdf->url();

        $this->assertStringStartsWith('http', $url);
        $this->assertStringContainsString($pdf->content_url, $url);
    }

    public function test_url_es_nula_cuando_no_hay_archivo(): void
    {
        $texto = ClassContent::factory()->create(['content_url' => null]);

        $this->assertNull($texto->url());
    }
}
