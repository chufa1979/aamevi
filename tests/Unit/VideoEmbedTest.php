<?php

namespace Tests\Unit;

use App\Models\ClassContent;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class VideoEmbedTest extends TestCase
{
    /** @return array<string, array{0: string, 1: string}> */
    public static function enlacesDeYoutube(): array
    {
        return [
            'watch clásico' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'watch sin www' => ['https://youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'watch con parámetros previos' => ['https://www.youtube.com/watch?app=desktop&v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'watch con tiempo' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=42s', 'dQw4w9WgXcQ'],
            'enlace corto' => ['https://youtu.be/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'enlace corto con tiempo' => ['https://youtu.be/dQw4w9WgXcQ?t=42', 'dQw4w9WgXcQ'],
            'ya embebido' => ['https://www.youtube.com/embed/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'en vivo' => ['https://www.youtube.com/live/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'con guiones y guión bajo' => ['https://youtu.be/a-B_cD1e2F3', 'a-B_cD1e2F3'],
        ];
    }

    #[DataProvider('enlacesDeYoutube')]
    public function test_reconoce_las_formas_de_youtube(string $url, string $id): void
    {
        $this->assertSame(
            "https://www.youtube.com/embed/{$id}",
            ClassContent::embedUrlFor($url),
        );
    }

    public function test_reconoce_vimeo(): void
    {
        $this->assertSame(
            'https://player.vimeo.com/video/76979871',
            ClassContent::embedUrlFor('https://vimeo.com/76979871'),
        );

        $this->assertSame(
            'https://player.vimeo.com/video/76979871',
            ClassContent::embedUrlFor('https://player.vimeo.com/video/76979871'),
        );
    }

    /** @return array<string, array{0: ?string}> */
    public static function enlacesNoIncrustables(): array
    {
        return [
            'nulo' => [null],
            'vacío' => [''],
            'archivo suelto' => ['https://storage.googleapis.com/aamevi/clase-1.mp4'],
            'otra plataforma' => ['https://ejemplo.com/video/123'],
            'canal de YouTube' => ['https://www.youtube.com/@aamevi'],
        ];
    }

    #[DataProvider('enlacesNoIncrustables')]
    public function test_devuelve_null_cuando_no_puede_incrustar(?string $url): void
    {
        $this->assertNull(ClassContent::embedUrlFor($url));
    }
}
