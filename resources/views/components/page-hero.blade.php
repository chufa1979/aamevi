@props([
    'title',
    /* Imagen de cabecera. Sin ella se usa un degradado con los colores del isotipo. */
    'image' => null,
    /* `small` recorta la cabecera a 200px, como `.header.small` del sitio madre. */
    'size' => 'small',
])

{{--
    Cabecera de sección: imagen a sangre, título blanco de peso liviano alineado
    abajo a la izquierda y borde inferior verde de 6px (patrón `.header` de
    www.aamevi.ar).
--}}
<section class="relative border-b-[6px] border-primary">
    @if ($image)
        <img src="{{ $image }}" alt=""
             @class(['block w-full object-cover', 'h-[200px]' => $size === 'small', 'h-auto' => $size !== 'small'])>
    @else
        <div @class([
            'w-full bg-gradient-to-r from-pillar-blue via-primary to-pillar-green',
            'h-[200px]' => $size === 'small',
            'h-[320px]' => $size !== 'small',
        ])></div>
    @endif

    <div class="container-site-sm absolute bottom-4 left-1/2 w-full -translate-x-1/2">
        <h1 class="font-light text-white [font-size:clamp(2.5rem,10vw,7.5rem)]">{{ $title }}</h1>
        {{ $slot }}
    </div>
</section>
