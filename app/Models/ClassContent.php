<?php

namespace App\Models;

use App\Enums\ClassContentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Database\Factories\ClassContentFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Un ítem de contenido dentro de una clase: un video, un PDF, un texto o el
 * enunciado de una tarea.
 */
class ClassContent extends Model
{
    /** @use HasFactory<ClassContentFactory> */
    use HasFactory, HasUuids;

    protected $table = 'class_content';

    protected $fillable = [
        'class_id',
        'type',
        'title',
        'description',
        'content_url',
        'content_file',
        'order_number',
    ];

    /** Para que el formulario pueda leer el archivo al editar. */
    protected $appends = ['content_file'];

    protected function casts(): array
    {
        return [
            'type' => ClassContentType::class,
            'order_number' => 'integer',
        ];
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(CourseClass::class, 'class_id');
    }

    /**
     * Alias de `content_url` para el campo de subida de archivos.
     *
     * Dos componentes de un mismo formulario no pueden compartir la ruta de
     * estado: el enlace de video y la subida de PDF escriben en la misma
     * columna, pero necesitan nombres distintos o se pisan entre sí y la
     * columna termina en null.
     */
    protected function contentFile(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): ?string => $attributes['content_url'] ?? null,
            set: fn (?string $value): array => ['content_url' => $value],
        );
    }

    /**
     * URL para mostrar el contenido. `content_url` guarda una URL completa
     * cuando el material es externo (Google Cloud Storage, un video alojado
     * afuera) y una ruta relativa cuando el archivo se subió al disco público.
     */
    public function url(): ?string
    {
        if (blank($this->content_url)) {
            return null;
        }

        if (str_starts_with($this->content_url, 'http://') || str_starts_with($this->content_url, 'https://')) {
            return $this->content_url;
        }

        return Storage::disk('public')->url($this->content_url);
    }

    /** URL embebible del video, o null si no se puede incrustar. */
    public function embedUrl(): ?string
    {
        return static::embedUrlFor($this->content_url);
    }

    /**
     * Convierte el enlace de un video en su URL para `<iframe>`.
     *
     * Es estático para poder previsualizar lo que se está tipeando en el panel,
     * antes de que exista el registro.
     *
     * YouTube reparte el mismo video en varias formas —`watch?v=`, `youtu.be`,
     * `/embed/`, `/shorts/`, `/live/`— y ninguna sirve directamente dentro de un
     * iframe salvo la de `/embed/`. Devuelve null para cualquier otro origen:
     * ahí lo correcto es ofrecer el enlace, no incrustar algo que no va a cargar.
     */
    public static function embedUrlFor(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $youtube = '~(?:youtube\.com/(?:watch\?(?:[^#]*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})~i';

        if (preg_match($youtube, $url, $matches) === 1) {
            return "https://www.youtube.com/embed/{$matches[1]}";
        }

        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~i', $url, $matches) === 1) {
            return "https://player.vimeo.com/video/{$matches[1]}";
        }

        return null;
    }
}
