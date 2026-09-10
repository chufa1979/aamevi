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

    Con foto, la altura de `full` baja un 20% (320px → 256px) respecto del
    degradado: una foto a esa altura ya se lee bien, y a 320px de alto
    recortaba de más el encuadre. `object-cover` sobre altura fija es lo que
    permite ese recorte controlado — con `h-auto` la imagen se ve completa
    pero a su relación de aspecto original, no a la del contenedor.
--}}
<section class="relative border-b-[6px] border-primary">
    @if ($image)
        <img src="{{ $image }}" alt=""
             @class(['block w-full object-cover', 'h-[200px]' => $size === 'small', 'h-[256px]' => $size !== 'small'])>

        {{-- Degradado oscuro: sin esto el título blanco se pierde contra el
             cielo claro de una foto, que es justo donde cae el texto. --}}
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent"></div>
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
