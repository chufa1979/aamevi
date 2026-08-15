@props(['content'])

{{--
    Un ítem de material de la clase, mostrado *dentro* de la página.

    El aula de referencia lista todo como enlaces que abren pestañas nuevas: el
    alumno termina con seis solapas y pierde el hilo de la clase. Acá el video se
    incrusta, el PDF se muestra en un visor, y el texto y las consignas se
    renderizan; el enlace externo queda como salida, no como única opción.
--}}
@php
    $tipo = $content->type;
    $url = $content->url();
    $embed = $content->embedUrl();
@endphp

<article class="card overflow-hidden">
    <header class="flex items-center gap-2 border-b border-line px-5 py-3">
        <x-ui.icon :name="match ($tipo) {
            \App\Enums\ClassContentType::Video => 'video',
            \App\Enums\ClassContentType::Pdf => 'pdf',
            \App\Enums\ClassContentType::Task => 'tarea',
            default => 'texto',
        }" class="h-4 w-4 shrink-0 text-subtle" />

        <h3 class="text-sm font-medium">
            {{ $content->title ?: $tipo->getLabel() }}
        </h3>

        <span class="ml-auto text-[11px] uppercase tracking-wide text-subtle">{{ $tipo->getLabel() }}</span>
    </header>

    <div class="p-5">
        @switch($tipo)
            @case(\App\Enums\ClassContentType::Video)
                @if ($embed)
                    <div class="aspect-video w-full overflow-hidden rounded bg-black">
                        <iframe src="{{ $embed }}"
                                title="{{ $content->title ?: 'Video de la clase' }}"
                                class="h-full w-full"
                                loading="lazy"
                                allowfullscreen
                                referrerpolicy="strict-origin-when-cross-origin"></iframe>
                    </div>
                @elseif ($url)
                    {{-- Origen que no se puede incrustar: mejor ofrecer el enlace
                         que un marco que no va a cargar --}}
                    <p class="text-sm">
                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="text-brand-text">
                            Ver el video en su sitio original
                        </a>
                    </p>
                @else
                    <p class="text-sm text-subtle">El video todavía no está cargado.</p>
                @endif
                @break

            @case(\App\Enums\ClassContentType::Pdf)
                @if ($url)
                    <object data="{{ $url }}"
                            type="application/pdf"
                            class="h-[32rem] w-full rounded border border-line"
                            aria-label="{{ $content->title ?: 'Documento de la clase' }}">
                        {{-- Los navegadores sin visor de PDF caen acá --}}
                        <p class="p-4 text-sm">
                            Tu navegador no puede mostrar el documento acá adentro.
                        </p>
                    </object>

                    <p class="mt-3 text-sm">
                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="text-brand-text">
                            Abrir o descargar el documento
                        </a>
                    </p>
                @else
                    <p class="text-sm text-subtle">El documento todavía no está cargado.</p>
                @endif
                @break

            @default
                <x-rich-text :html="$content->description" class="text-sm" />
        @endswitch
    </div>
</article>
