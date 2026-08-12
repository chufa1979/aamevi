{{--
    Previsualización del video dentro del panel. `$embed` llega null cuando el
    origen no se puede incrustar (un enlace directo a un archivo, otra
    plataforma); en ese caso se ofrece abrirlo, que es lo único honesto.
--}}
@if ($embed)
    <div class="aspect-video w-full overflow-hidden rounded-lg bg-black">
        <iframe
            src="{{ $embed }}"
            class="h-full w-full"
            loading="lazy"
            referrerpolicy="strict-origin-when-cross-origin"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture"
            allowfullscreen
        ></iframe>
    </div>
@elseif ($url)
    <a
        href="{{ $url }}"
        target="_blank"
        rel="noreferrer"
        class="text-sm text-primary-600 underline underline-offset-2 dark:text-primary-400"
    >
        Abrir el video en una pestaña nueva
    </a>

    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
        No se puede incrustar este origen. Se reconocen YouTube y Vimeo.
    </p>
@endif
