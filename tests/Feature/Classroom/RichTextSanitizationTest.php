<?php

namespace Tests\Feature\Classroom;

use Tests\TestCase;
use App\Support\Html;
use Illuminate\Support\Facades\Blade;

/**
 * El aula muestra con formato el marcado que se carga desde el panel. Sin
 * sanitizar, cualquiera con acceso al editor podría inyectar un script que se
 * ejecuta en la sesión de cada alumno que abra la clase.
 */
class RichTextSanitizationTest extends TestCase
{
    public function test_conserva_el_formato_legitimo(): void
    {
        $limpio = Html::sanitize('<p>Texto con <strong>negrita</strong> y <em>cursiva</em>.</p><ul><li>Uno</li></ul>');

        $this->assertStringContainsString('<strong>negrita</strong>', $limpio);
        $this->assertStringContainsString('<em>cursiva</em>', $limpio);
        $this->assertStringContainsString('<li>Uno</li>', $limpio);
    }

    public function test_quita_los_scripts(): void
    {
        $limpio = Html::sanitize('<p>Hola</p><script>alert(document.cookie)</script>');

        $this->assertStringNotContainsString('<script', $limpio);
        $this->assertStringNotContainsString('alert', $limpio);
        $this->assertStringContainsString('Hola', $limpio);
    }

    public function test_quita_los_manejadores_de_evento(): void
    {
        $limpio = Html::sanitize('<p onclick="robar()">Texto</p><img src=x onerror="robar()">');

        $this->assertStringNotContainsString('onclick', $limpio);
        $this->assertStringNotContainsString('onerror', $limpio);
    }

    /** `javascript:` en un href es ejecutable: no alcanza con permitir la etiqueta. */
    public function test_quita_los_enlaces_ejecutables(): void
    {
        $limpio = Html::sanitize('<a href="javascript:robar()">Clic</a>');

        $this->assertStringNotContainsString('javascript:', $limpio);
    }

    public function test_conserva_los_enlaces_navegables_y_los_asegura(): void
    {
        $limpio = Html::sanitize('<a href="https://www.aamevi.ar">AAMEVi</a>');

        $this->assertStringContainsString('https://www.aamevi.ar', $limpio);
        $this->assertStringContainsString('rel="noopener noreferrer"', $limpio);
    }

    public function test_quita_los_iframes(): void
    {
        $limpio = Html::sanitize('<iframe src="https://evil.example"></iframe><p>Texto</p>');

        $this->assertStringNotContainsString('<iframe', $limpio);
    }

    public function test_el_vacio_no_rompe(): void
    {
        $this->assertSame('', Html::sanitize(null));
        $this->assertSame('', Html::sanitize(''));
    }

    /** El componente es el único punto por donde el aula imprime HTML sin escapar. */
    public function test_el_componente_sanitiza(): void
    {
        $render = Blade::render(
            '<x-rich-text :html="$html" />',
            ['html' => '<p>Bien</p><script>mal()</script>'],
        );

        $this->assertStringContainsString('<p>Bien</p>', $render);
        $this->assertStringNotContainsString('<script', $render);
    }

    public function test_el_componente_no_deja_un_div_vacio(): void
    {
        $this->assertSame('', trim(Blade::render('<x-rich-text :html="null" />')));
    }

    /**
     * Guarda contra el olvido: si alguien imprime marcado del editor con
     * `{!! !!}` en una vista del aula, este test lo caza.
     */
    public function test_ninguna_vista_del_aula_imprime_html_sin_sanitizar(): void
    {
        $sospechosas = [];

        foreach (glob(resource_path('views/classroom/**/*.blade.php'), GLOB_BRACE) ?: [] as $vista) {
            if (str_contains((string) file_get_contents($vista), '{!!')) {
                $sospechosas[] = str_replace(resource_path('views/'), '', $vista);
            }
        }

        $this->assertSame([], $sospechosas, 'Usá <x-rich-text> en lugar de {!! !!}.');
    }
}
