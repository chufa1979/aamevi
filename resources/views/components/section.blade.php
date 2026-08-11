@props([
    'title' => null,
    'subtitle' => null,
    /* Usa el contenedor angosto (1070px) en lugar del ancho (1315px). */
    'narrow' => false,
])

{{-- Bloque de contenido con el ritmo vertical del sitio institucional. --}}
<section {{ $attributes->merge(['class' => 'py-8 md:py-16']) }}>
    <div class="{{ $narrow ? 'container-site-sm' : 'container-site' }}">
        @if ($title)
            <h2 class="section-title mb-4">{{ $title }}</h2>
        @endif

        @if ($subtitle)
            <p class="section-subtitle mb-6 font-light">{{ $subtitle }}</p>
        @endif

        {{ $slot }}
    </div>
</section>
